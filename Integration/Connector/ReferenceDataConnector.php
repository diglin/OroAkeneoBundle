<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Connector;

use Oro\Bundle\AkeneoBundle\Entity\ReferenceDataInterface;
use Oro\Bundle\AkeneoBundle\Integration\AkeneoTransportInterface;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

/**
 * @property AkeneoTransportInterface $transport
 */
class ReferenceDataConnector extends AbstractConnector
{
    const IMPORT_JOB_NAME = 'akeneo_reference_data_import';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.akeneo.connector.reference_data.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return ReferenceDataInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'reference_data';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getReferenceData();
    }
}
