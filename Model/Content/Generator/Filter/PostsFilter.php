<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Content\Generator\Filter;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Model\ResourceModel\Post\Collection as PostResourceCollection;

class PostsFilter
{
    /**
     * Filters Posts entities so that they are unique: If there is a store-specific entity, remove the global entity
     *
     * @throws LocalizedException
     */
    public function filterEntities(AbstractDb $entities, array $identifiers): AbstractDb
    {
        if (!$entities instanceof PostResourceCollection) {
            return $entities;
        }

        // make sure the stores are loaded
        $entities->walk('afterLoad');
        foreach ($identifiers as $identifier) {
            $entitiesWithIdentifier = $entities->getItemsByColumnValue(PostInterface::URL_KEY, $identifier);
            if (count($entitiesWithIdentifier) < 2) {
                continue;
            }
            if (count($entitiesWithIdentifier) > 2) {
                throw new LocalizedException(__('The collection has more than two entities per identifier.'));
            }
            [$entityWithIdentifier1, $entityWithIdentifier2] = $entitiesWithIdentifier;
            if (in_array(0, $entityWithIdentifier1->getData('store_ids'), false)) {
                $entityToRemove = $entityWithIdentifier1;
            } else {
                $entityToRemove = $entityWithIdentifier2;
            }
            $entities->removeItemByKey($entityToRemove->getId());
        }

        return $entities;
    }
}
