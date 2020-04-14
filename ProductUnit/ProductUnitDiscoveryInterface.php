<?php declare(strict_types=1);

namespace Oro\Bundle\AkeneoBundle\ProductUnit;

use Oro\Bundle\AkeneoBundle\Entity\AkeneoSettings;

interface ProductUnitDiscoveryInterface
{
    public function discover(AkeneoSettings $transport, array $importedRecord): array;
}
