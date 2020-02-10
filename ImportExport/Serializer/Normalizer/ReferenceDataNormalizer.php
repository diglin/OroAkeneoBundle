<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\AkeneoBundle\Integration\AkeneoChannel;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

class ReferenceDataNormalizer extends ConfigurableEntityNormalizer
{
    /** @var array */
    protected $referenceDataClasses = [];

    public function __construct(
        FieldHelper $fieldHelper,
        array $referenceDataClasses
    ) {
        parent::__construct($fieldHelper);
        $this->referenceDataClasses = $referenceDataClasses;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        $return = is_a($type, 'Oro\Bundle\AkeneoBundle\Entity\ReferenceDataInterface', true) &&
            true === isset($data['type']) &&
            true === isset($this->referenceDataClasses[$data['type']]) &&
            true === isset($context['channelType']) &&
            AkeneoChannel::TYPE === $context['channelType'];
        return $return;
    }

    /**
     * @param array $data
     * @param string $class
     * @param null $format
     * @param array $context
     * @return mixed|object
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $class = $this->referenceDataClasses[$data['type']] ?? $class;
        $context['entityName'] = $class;
        return parent::denormalize($data, $class, $format, $context);
    }
}
