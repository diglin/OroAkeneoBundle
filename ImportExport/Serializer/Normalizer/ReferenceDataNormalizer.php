<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\AkeneoBundle\Integration\AkeneoChannel;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ReferenceDataNormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    /** @var array */
    protected $referenceDataClasses = [];
    /**
     * @var \Symfony\Component\Serializer\SerializerInterface
     */
    private $serializer;

    public function __construct(array $referenceDataClasses)
    {
        $this->referenceDataClasses = $referenceDataClasses;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return ($type == 'Oro\Bundle\AkeneoBundle\Entity\ReferenceDataInterface')
            && isset($data['type'])
            && isset($this->referenceDataClasses[$data['type']])
            && isset($context['channelType']) && AkeneoChannel::TYPE === $context['channelType'];
    }

    /**
     * @param array $data
     * @param string $class
     * @param null $format
     * @param array $context
     *
     * @return mixed|object
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $class = $this->referenceDataClasses[$data['type']] ?? $class;
        $context['entityName'] = $class;

        return $this->serializer->denormalize($data, $class, $format, $context);
    }

    /**
     * @inheritDoc
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}
