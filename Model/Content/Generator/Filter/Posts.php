<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Content\Generator\Filter;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Model\ResourceModel\Post\Collection as PostResourceCollection;

/**
 * @see \EasyTranslate\Connector\Model\Content\Generator\Filter\Cms
 */
class Posts
{
    /**
     * Filters Posts entities so that they are unique: If there is a store-specific entity, remove the global entity
     *
     * @throws LocalizedException
     */
    public function filterEntities(AbstractDb $entities, array $urlKeys): AbstractDb
    {
        if (!$entities instanceof PostResourceCollection) {
            return $entities;
        }

        // make sure the stores are loaded
        $entities->walk('afterLoad');
        foreach ($urlKeys as $urlKey) {
            $entitiesWithUrlKeys = $entities->getItemsByColumnValue(PostInterface::URL_KEY, $urlKey);
            if (count($entitiesWithUrlKeys) < 2) {
                continue;
            }
            if (count($entitiesWithUrlKeys) > 2) {
                throw new LocalizedException(__('The collection has more than two entities per urlKey.'));
            }
            [$entityWithUrlKey1, $entityWithUrlKey2] = $entitiesWithUrlKeys;
            if (in_array(0, $entityWithUrlKey1->getData('store_ids'), false)) {
                $entityToRemove = $entityWithUrlKey1;
            } else {
                $entityToRemove = $entityWithUrlKey2;
            }
            $entities->removeItemByKey($entityToRemove->getId());
        }

        return $entities;
    }
}
