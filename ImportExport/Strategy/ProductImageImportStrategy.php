<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Strategy;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Strategy to import product images.
 */
class ProductImageImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    use ImportStrategyAwareHelperTrait;

    /**
     * @param ProductImage $entity
     *
     * @return object
     */
    protected function beforeProcessEntity($entity)
    {
        if (!$entity->getImage()) {
            return null;
        }

        return parent::beforeProcessEntity($entity);
    }
}
