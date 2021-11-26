<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Observer;

use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterProjectSaveObserver implements ObserverInterface
{
    /**
     * @var Posts
     */
    private $posts;

    public function __construct(Posts $posts)
    {
        $this->posts = $posts;
    }

    public function execute(Observer $observer): void
    {
        $this->posts->saveProjectPosts($observer->getData()['object']);
    }
}
