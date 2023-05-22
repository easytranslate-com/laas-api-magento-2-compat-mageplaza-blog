<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Observer;

use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts as PostsResource;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class AfterProjectSaveObserver implements ObserverInterface
{
    /**
     * @var PostsResource
     */
    private $postsResource;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    public function __construct(
        PostsResource $postsResource,
        RequestInterface $request,
        JsonSerializer $jsonSerializer
    ) {
        $this->postsResource  = $postsResource;
        $this->request        = $request;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function execute(Observer $observer): void
    {
        $selectedPosts = $this->request->getParam('mageplaza_selected_posts');
        if ($selectedPosts === null) {
            return;
        }
        $posts = $this->jsonSerializer->unserialize($selectedPosts);
        if (!is_array($posts)) {
            return;
        }
        $newPosts = array_map('intval', $posts);

        $this->postsResource->saveProjectPosts($observer->getData()['object'], $newPosts);
    }
}
