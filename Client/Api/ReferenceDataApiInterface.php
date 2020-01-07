<?php

namespace Oro\Bundle\AkeneoBundle\Client\Api;

use Akeneo\Pim\ApiClient\Api\Operation\GettableResourceInterface;

interface ReferenceDataApiInterface extends
    ApiAwareInterface,
    GettableResourceInterface
{
}
