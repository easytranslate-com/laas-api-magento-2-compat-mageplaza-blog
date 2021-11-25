<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Observer;

use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\MageplazaPosts;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class MageplazaPostsObserver implements ObserverInterface
{
    /**
     * @var MageplazaPosts
     */
    private $posts;

    public function __construct(MageplazaPosts $posts)
    {
        $this->posts = $posts;
    }

    public function execute(Observer $observer): void
    {
        $this->posts->saveProjectMageplazaPosts($observer->getData()['object']);
    }
}
