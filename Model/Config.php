<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_POSTS_ATTRIBUTES = 'easytranslate/mageplaza/attributes';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getPostAttributes(): array
    {
        $rawAttributes = $this->scopeConfig->getValue(
            self::XML_PATH_POSTS_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );
        if ($rawAttributes === null || $rawAttributes === '') {
            return [];
        }

        return explode(',', $rawAttributes);
    }
}
