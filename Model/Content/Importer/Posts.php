<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Content\Importer;

use EasyTranslate\Connector\Model\Content\Importer\AbstractCmsImporter;
use Magento\Framework\DB\TransactionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Blog\Model\Post;
use Mageplaza\Blog\Model\PostFactory;
use Mageplaza\Blog\Model\ResourceModel\Post as PostResource;
use Mageplaza\Blog\Model\ResourceModel\Post\CollectionFactory;

class Posts extends AbstractCmsImporter
{
    /**
     * @var CollectionFactory
     */
    private $postCollection;

    /**
     * @var PostResource
     */
    private $postResource;

    /**
     * @var PostFactory
     */
    private $postFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        TransactionFactory $transactionFactory,
        CollectionFactory $postCollection,
        PostResource $postResource,
        PostFactory $postFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($transactionFactory);

        $this->postCollection = $postCollection;
        $this->postResource   = $postResource;
        $this->postFactory    = $postFactory;
        $this->storeManager   = $storeManager;
    }

    protected function importObject(string $id, array $attributes, int $sourceStoreId, int $targetStoreId): void
    {
        $post = $this->postCollection->create()->addAttributeToFilter('post_id', ['in' => $id])->getFirstItem();
        /** @var Post $post */
        $storeIds = (array)$post->getData('store_ids');
        if (in_array($targetStoreId, $storeIds, false) && count($storeIds) === 1) {
            $this->handleExistingUniquePost($post, $attributes);
        } elseif (in_array($targetStoreId, $storeIds, false) && count($storeIds) > 1) {
            $this->handleExistingPostWithMultipleStores($post, $attributes, $targetStoreId);
        } elseif (in_array(Store::DEFAULT_STORE_ID, $storeIds, false) && count($storeIds) >= 1) {
            $this->handleExistingGlobalPost($post, $attributes, $targetStoreId);
        } else {
            // this should rarely happen - only if the post from the source store has been deleted in the meantime
            $post->setData('identifier', $id);
            $this->handleNonExistingPost($post, $attributes, $targetStoreId);
        }
    }

    private function handleExistingGlobalPost(Post $post, array $newData, int $targetStoreId): void
    {
        if (!isset($newData['identifier'])
            || $post['identifier'] === $newData['identifier']) {
            // make sure that the URL key is unique by moving the existing global store to the respective store views
            $allStores   = $this->storeManager->getStores();
            $allStoreIds = array_map(static function ($store) {
                return (int)$store->getId();
            }, $allStores);
            $newStoreIds = array_values(array_diff($allStoreIds, [$targetStoreId]));
            $post->setData('store_id', $newStoreIds);
            $post->setData('stores', $newStoreIds);
            $this->postResource->save($post);
        }

        $this->createNewPostForStore($post, $newData, $targetStoreId);
    }

    private function handleExistingUniquePost(Post $post, array $newData): void
    {
        $post->addData($newData);
        $this->objects[] = $post;
    }

    private function handleExistingPostWithMultipleStores(Post $post, array $newData, int $targetStoreId): void
    {
        // first remove the current store ID from the existing post, because posts must be unique per store
        $storeIds    = (array)$post->getData('store_ids');
        $newStoreIds = array_diff($storeIds, [$targetStoreId]);
        $post->setData('store_ids', $newStoreIds);
        $post->setData('stores', $newStoreIds);
        $this->postResource->save($post);
        $this->createNewPostForStore($post, $newData, $targetStoreId);
    }

    private function handleNonExistingPost(Post $post, array $newData, int $targetStoreId): void
    {
        $this->createNewPostForStore($post, $newData, $targetStoreId);
    }

    private function createNewPostForStore(Post $basePost, array $newData, int $targetStoreId): void
    {
        $post = $this->postFactory->create();
        $post->addData($basePost->getData());
        $post->addData($newData);
        // make sure that a new post is created!
        $post->unsetData('post_id');
        $post->unsetData('created_at');
        $post->setData('store_ids', [$targetStoreId]);
        $this->objects[] = $post;
    }
}
