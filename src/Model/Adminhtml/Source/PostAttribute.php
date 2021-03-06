<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mageplaza\Blog\Api\Data\PostInterface;

class PostAttribute implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => PostInterface::NAME,
                'label' => __('Post Name'),
            ],
            [
                'value' => PostInterface::SHORT_DESCRIPTION,
                'label' => __('Short Description'),
            ],
            [
                'value' => PostInterface::POST_CONTENT,
                'label' => __('Post Content'),
            ],
            [
                'value' => PostInterface::URL_KEY,
                'label' => __('Url Key'),
            ],
            [
                'value' => PostInterface::META_TITLE,
                'label' => __('Meta Title'),
            ],
            [
                'value' => PostInterface::META_KEYWORDS,
                'label' => __('Meta Keywords'),
            ],
            [
                'value' => PostInterface::META_DESCRIPTION,
                'label' => __('Meta Description'),
            ]
        ];
    }
}
