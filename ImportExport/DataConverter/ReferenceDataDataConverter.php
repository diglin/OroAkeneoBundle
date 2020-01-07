<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\DataConverter;

use Oro\Bundle\AkeneoBundle\Tools\Generator;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

class ReferenceDataDataConverter extends LocalizedFallbackValueAwareDataConverter implements ContextAwareInterface
{
    use AkeneoIntegrationTrait;

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        /** @TODO : import image */
        unset($importedRecord['image']);

        $this->setLabels($importedRecord);
        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * Set labels with locales mapping from settings.
     *
     * @param array $importedRecord
     */
    private function setLabels(array &$importedRecord)
    {
        $importedRecord['labels'] = [
            'default' => [
                'fallback' => null,
                'string' => Generator::generateLabel($importedRecord['label']),
            ],
        ];
    }
}
