<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Observer;

use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts as PostsResource;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\DecoderInterface;

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
     * @var DecoderInterface
     */
    private $decoder;

    public function __construct(
        PostsResource $postsResource,
        RequestInterface $request,
        DecoderInterface $decoder
    ) {
        $this->postsResource = $postsResource;
        $this->request       = $request;
        $this->decoder       = $decoder;
    }

    public function execute(Observer $observer): void
    {
        $selectedPosts = $this->request->getParam('mageplaza_selected_posts');
        if ($selectedPosts === null) {
            return;
        }
        $posts = $this->decoder->decode($selectedPosts);
        if (!is_array($posts)) {
            return;
        }
        $newPosts = array_map('intval', $posts);

        $this->postsResource->saveProjectPosts($observer->getData()['object'], $newPosts);
    }
}
