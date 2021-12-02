<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Content\Importer;

use EasyTranslate\Connector\Model\Content\Importer\AbstractCmsImporter;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Blog\Api\Data\PostInterface;
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

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function importObject(string $id, array $attributes, int $sourceStoreId, int $targetStoreId): void
    {
        $post     = $this->loadBasePost($id, $sourceStoreId, $targetStoreId);
        $storeIds = (array)$post->getData(PostInterface::STORE_IDS);
        if (in_array($targetStoreId, $storeIds, false) && count($storeIds) === 1) {
            $this->handleExistingUniquePost($post, $attributes);
        } elseif (in_array(Store::DEFAULT_STORE_ID, $storeIds, false) && count($storeIds) >= 1) {
            $this->handleExistingGlobalPost($post, $attributes, $targetStoreId);
        } elseif (in_array($targetStoreId, $storeIds, false) && count($storeIds) > 1) {
            $this->handleExistingPostWithMultipleStores($post, $attributes, $targetStoreId);
        } else {
            // this should rarely happen - only if the post from the source store has been deleted in the meantime
            $post->setData(PostInterface::URL_KEY, $id);
            $this->handleNonExistingPost($post, $attributes, $targetStoreId);
        }
    }

    private function loadBasePost(string $urlKey, int $sourceStoreId, int $targetStoreId): Post
    {
        $postFromTargetStore = $this->loadExistingPost($urlKey, $targetStoreId);
        if ($postFromTargetStore->getId()) {
            // if there is already a post in the target store, use it as a base
            return $postFromTargetStore;
        }

        // otherwise, use the post from the source store as a base
        return $this->loadExistingPost($urlKey, $sourceStoreId);
    }

    private function loadExistingPost(string $urlKey, int $storeId): Post
    {
        try {
            $post = $this->postFactory->create();
            $post->setData('store_ids', $storeId);
            $this->postResource->load($post, $urlKey, PostInterface::URL_KEY);
            if (!$post->getId()) {
                throw new NoSuchEntityException(__('The post with the "%1" url key doesn\'t exist.', $urlKey));
            }

            return $post;
        } catch (NoSuchEntityException $e) {
            $post = $this->postFactory->create();
            /** @var Post $post */
            $post->setData('store_ids', $storeId);

            return $post;
        }
    }

    private function handleExistingGlobalPost(Post $post, array $newData, int $targetStoreId): void
    {
        if (!isset($newData[PostInterface::URL_KEY])
            || $post[PostInterface::URL_KEY] === $newData[PostInterface::URL_KEY]) {
            // make sure that the URL key is unique by moving the existing global store to the respective store views
            $allStores   = $this->storeManager->getStores();
            $allStoreIds = array_map(static function ($store) {
                return (int)$store->getId();
            }, $allStores);
            $newStoreIds = array_values(array_diff($allStoreIds, [$targetStoreId]));
            $post->setData(PostInterface::STORE_IDS, $newStoreIds);
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
        $storeIds    = (array)$post->getData(PostInterface::STORE_IDS);
        $newStoreIds = array_diff($storeIds, [$targetStoreId]);
        $post->setData(PostInterface::STORE_IDS, $newStoreIds);
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
        $post->unsetData(PostInterface::POST_ID);
        $post->unsetData(PostInterface::CREATED_AT);
        $post->setData(PostInterface::STORE_IDS, [$targetStoreId]);
        $this->objects[] = $post;
    }
}
