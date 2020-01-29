<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Iterator;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;

class ProductIterator extends AbstractIterator
{
    /**
     * @var bool
     */
    private $attributesInitialized = false;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var bool
     */
    private $familyVariantsInitialized = false;

    /**
     * @var array
     */
    private $familyVariants = [];

    /**
     * @var AttributeIterator
     */
    private $attributesList;

    /**
     * @var string|null
     */
    private $alternativeAttribute;

    /**
     * AttributeIterator constructor.
     */
    public function __construct(
        ResourceCursorInterface $resourceCursor,
        AkeneoPimEnterpriseClientInterface $client,
        LoggerInterface $logger,
        AttributeIterator $attributeList,
        ?string $alternativeAttribute = null
    ) {
        parent::__construct($resourceCursor, $client, $logger);
        $this->attributesList = $attributeList;
        $this->alternativeAttribute = $alternativeAttribute;

        $this->initAttributesList();
    }

    protected function initAttributesList()
    {
        if (!$this->attributesInitialized) {
            foreach ($this->attributesList as $attribute) {
                if (null === $attribute) {
                    continue;
                }

                $this->attributes[$attribute['code']] = $attribute;
            }
            $this->attributesInitialized = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doCurrent()
    {
        $product = $this->resourceCursor->current();

        $this->setAlternativeIdentifier($product);
        $this->setValueAttributeTypes($product);
        $this->setFamilyVariant($product);

        return $product;
    }

    /**
     * Switch the product code (intern identifier in Akeneo) value
     * with an other attribute to allow to map it differently
     */
    protected function setAlternativeIdentifier(array &$product)
    {
        if (null === $this->alternativeAttribute) return;

        @list($altAttribute, $identifier) = explode(':', $this->alternativeAttribute);

        if (!empty($altAttribute)
            && isset($product['values'][$altAttribute])
            && isset($product['identifier'])
        ) {

            if (isset($product['values'][$altAttribute][0]['data'])) {
                if (null !== $identifier) {
                    $product[$identifier] = $product['identifier'];
                }

                $product['identifier'] = $product['values'][$altAttribute][0]['data'];
            }
        }
    }

    /**
     * Set attribute types for product values.
     */
    protected function setValueAttributeTypes(array &$product)
    {
        if (false === $this->attributesInitialized) {
            foreach ($this->attributesList as $attribute) {
                if (null === $attribute) {
                    continue;
                }

                $this->attributes[$attribute['code']] = $attribute;
            }
            $this->attributesInitialized = true;
        }

        foreach ($product['values'] as $code => $values) {
            if (isset($this->attributes[$code])) {
                foreach ($values as $key => $value) {
                    $product['values'][$code][$key]['type'] = $this->attributes[$code]['type'];
                }
            } else {
                unset($product['values'][$code]);
            }
        }
    }

    /**
     * Set family variant from API.
     */
    private function setFamilyVariant(array &$model)
    {
        if (false === $this->familyVariantsInitialized) {
            foreach ($this->client->getFamilyApi()->all(self::PAGE_SIZE) as $family) {
                foreach ($this->client->getFamilyVariantApi()->all($family['code'], self::PAGE_SIZE) as $variant) {
                    $variant['family'] = $family['code'];
                    $this->familyVariants[$variant['code']] = $variant;
                }
            }
            $this->familyVariantsInitialized = true;
        }

        if (empty($model['family_variant'])) {
            return;
        }

        if (isset($this->familyVariants[$model['family_variant']])) {
            $model['family_variant'] = $this->familyVariants[$model['family_variant']];
        }
    }
}
