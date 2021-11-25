<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Controller\Adminhtml\Project\MageplazaBlogs;

use EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab\MageplazaBlogs;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Controller\Adminhtml\Project\AbstractEntityGrid;

class Grid extends AbstractEntityGrid
{
    protected function getGridBlock(): AbstractEntity
    {
        return $this->layoutFactory->create()->createBlock(MageplazaBlogs::class, 'project.mageplazablog.grid');
    }
}
