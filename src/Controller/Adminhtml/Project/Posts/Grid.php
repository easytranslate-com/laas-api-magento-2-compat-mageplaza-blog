<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Controller\Adminhtml\Project\Posts;

use EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab\Posts;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Controller\Adminhtml\Project\AbstractEntityGrid;

class Grid extends AbstractEntityGrid
{
    protected function getGridBlock(): AbstractEntity
    {
        return $this->layoutFactory->create()->createBlock(Posts::class, 'project.mageplaza_blog_posts.grid');
    }
}
