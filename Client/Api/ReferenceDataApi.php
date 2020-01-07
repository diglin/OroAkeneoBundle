<?php

namespace Oro\Bundle\AkeneoBundle\Client\Api;

use Akeneo\Pim\ApiClient\Client\ResourceClientInterface;
use Akeneo\Pim\ApiClient\Pagination\PageFactoryInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorFactoryInterface;

class ReferenceDataApi implements ReferenceDataApiInterface
{
    const REFERENCE_DATA_URI = 'api/rest/v1/reference-data/%s';
    const REFERENCE_DATA_ELEMENT_URI = 'api/rest/v1/reference-data/%s/%s';

    use ApiAwareTrait;

    /** @var ResourceClientInterface */
    protected $resourceClient;

    /** @var PageFactoryInterface */
    protected $pageFactory;

    /** @var ResourceCursorFactoryInterface */
    protected $cursorFactory;

    /**
     * {@inheritdoc}
     */
    public function get($code)
    {
        return $this->resourceClient->getResource(static::REFERENCE_DATA_URI, [$code]);
    }
}
