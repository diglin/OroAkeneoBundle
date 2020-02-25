<?php

namespace Oro\Bundle\AkeneoBundle\Integration;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\AkeneoBundle\Client\AkeneoClient;
use Oro\Bundle\AkeneoBundle\Client\AkeneoClientFactory;
use Oro\Bundle\AkeneoBundle\Entity\AkeneoSettings;
use Oro\Bundle\AkeneoBundle\Form\Type\AkeneoSettingsType;
use Oro\Bundle\AkeneoBundle\Integration\Iterator\AttributeFamilyIterator;
use Oro\Bundle\AkeneoBundle\Integration\Iterator\AttributeIterator;
use Oro\Bundle\AkeneoBundle\Integration\Iterator\CategoryIterator;
use Oro\Bundle\AkeneoBundle\Integration\Iterator\ProductIterator;
use Oro\Bundle\AkeneoBundle\Integration\Iterator\ReferenceDataIterator;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\MultiCurrencyBundle\Config\MultiCurrencyConfigProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Intl\Intl;

class AkeneoTransport implements AkeneoTransportInterface
{
    const PAGE_SIZE = 100;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $familyVariants = [];

    /**
     * @var AkeneoClientFactory
     */
    private $clientFactory;

    /**
     * @var AkeneoClient
     */
    private $client;

    /**
     * @var MultiCurrencyConfigProvider
     */
    private $configProvider;

    /**
     * @var AkeneoSettings
     */
    private $transportEntity;

    /**
     * @var AkeneoSearchBuilder
     */
    private $akeneoSearchBuilder;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        AkeneoClientFactory $clientFactory,
        MultiCurrencyConfigProvider $configProvider,
        AkeneoSearchBuilder $akeneoSearchBuilder,
        FilesystemMap $filesystemMap,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->configProvider = $configProvider;
        $this->akeneoSearchBuilder = $akeneoSearchBuilder;
        $this->filesystem = $filesystemMap->get('importexport');
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity, $tokensEnabled = true)
    {
        $this->client = $this->clientFactory->getInstance($transportEntity, $tokensEnabled);
        $this->transportEntity = $transportEntity;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        $currencies = [];

        foreach ($this->client->getCurrencyApi()->all() as $currency) {
            if (false === $currency['enabled']) {
                continue;
            }

            $currencies[] = $currency['code'];
        }

        return $currencies;
    }

    /**
     * @return array
     */
    public function getMergedCurrencies()
    {
        $currencies = [];
        $oroCurrencies = $this->configProvider->getCurrencies();

        foreach ($this->client->getCurrencyApi()->all() as $currency) {
            if (false === $currency['enabled']) {
                continue;
            }
            if (in_array($currency['code'], $oroCurrencies)) {
                $currencies[$currency['code']] = $currency['code'];
            }
        }

        return $currencies;
    }

    public function setConfigProvider(MultiCurrencyConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        $locales = [];

        foreach ($this->client->getLocaleApi()->all() as $locale) {
            if (false === $locale['enabled']) {
                continue;
            }

            $localeName = Intl::getLocaleBundle()->getLocaleName($locale['code']);
            $locales[$localeName ?: $locale['code']] = $locale['code'];
        }

        return $locales;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        $channels = [];
        foreach ($this->client->getChannelApi()->all() as $channel) {
            $channels[$channel['code']] = $channel['code'];
        }

        return $channels;
    }

    /**
     * @return \Iterator
     */
    public function getCategories(int $pageSize)
    {
        $categoryTreeChannel = null;
        $akeneoChannel = $this->transportEntity->getAkeneoActiveChannel();

        if (!empty($akeneoChannel)) {
            foreach ($this->client->getChannelApi()->all() as $channel) {

                $categoryTreeChannel = ($channel['code'] == $akeneoChannel && !empty($channel['category_tree'])) ? $channel['category_tree'] : null;

                if (null !== $categoryTreeChannel) {
                    break;
                }
            }
        }

        if (null === $categoryTreeChannel) {
            return $this->client->getCategoryApi()->all($pageSize);
        }

        $parentCategory = [];
        $akeneoTree = new \ArrayIterator([], \ArrayIterator::STD_PROP_LIST);

        foreach ($this->client->getCategoryApi()->all($pageSize) as $category) {
            if ($category['code'] == $categoryTreeChannel || in_array($category['parent'], $parentCategory)) {
                $parentCategory[] = $category['code'];
                $akeneoTree->append($category);
            }
        }
        unset($parentCategory);

        return $akeneoTree;
    }

    /**
     * @return \Iterator
     */
    public function getAttributeFamilies()
    {
        return new AttributeFamilyIterator($this->client->getFamilyApi()->all(), $this->client, $this->logger);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Iterator
     */
    public function getProducts(int $pageSize)
    {
        $this->initAttributesList();

        $searchFilters = $this->akeneoSearchBuilder->getFilters($this->transportEntity->getProductFilter());

        return new ProductIterator(
            $this->client->getProductApi()->all(
                $pageSize,
                ['search' => $searchFilters, 'scope' => $this->transportEntity->getAkeneoActiveChannel()]
            ),
            $this->client,
            $this->logger,
            $this->attributes,
            [],
            $this->getAlternativeIdentifier()
        );
    }

    /**
     * @return \Iterator
     */
    public function getProductModels(int $pageSize)
    {
        $this->initAttributesList();
        $this->initFamilyVariants();

        $searchFilters = $this->akeneoSearchBuilder->getFilters($this->transportEntity->getProductFilter());

        return new ProductIterator(
            $this->client->getProductModelApi()->all(
                $pageSize,
                ['search' => $searchFilters, 'scope' => $this->transportEntity->getAkeneoActiveChannel()]
            ),
            $this->client,
            $this->logger,
            $this->familyVariants,
            $this->attributes
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return AkeneoSettingsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return AkeneoSettings::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.akeneo.integration.settings.label';
    }

    /**
     * @return AttributeIterator
     */
    public function getAttributes(int $pageSize)
    {
        $attributeFilter = [];
        $attrList = $this->transportEntity->getAkeneoAttributesList();
        if (!empty($attrList)) {
            $attributeFilter = array_merge(
                explode(';', $attrList) ?? [],
                explode(';', $this->transportEntity->getAkeneoAttributesImageList()) ?? []
            );
        }

        return new AttributeIterator($this->client->getAttributeApi()->all($pageSize), $this->client, $this->logger, $attributeFilter);
    }

    /**
     * @return \Iterator
     */
    public function getReferenceData()
    {
        $referenceDataFilter = [];
        $referenceDataList = $this->transportEntity->getAkeneoReferenceDataList();
        if (!empty($referenceDataList)) {
            $referenceDataFilter = explode(';', str_replace(' ', '', $referenceDataList));
        }
        foreach ($referenceDataFilter as $referenceName) {
            foreach ($this->client->get('ReferenceDataApi')->get($referenceName) as $referenceDataItem) {
                $referenceDataItem['type'] = $referenceName;
                yield $referenceDataItem;
            }
        }
    }

    /**
     * @return null|string
     */
    private function getAlternativeIdentifier(): ?string
    {
        return $this->transportEntity->getAlternativeIdentifier();
    }

    public function downloadAndSaveMediaFile($type, $code)
    {
        $path = $this->getFilePath($type, $code);

        if ($this->filesystem->has($path)) {
            return;
        }

        try {
            $content = $this->client->getProductMediaFileApi()->download($code)->getContents();
        } catch (NotFoundHttpException $e) {
            $this->logger->critical(
                'Error on downloading media file.',
                ['message' => $e->getMessage(), 'exception' => $e]
            );

            return;
        }

        $this->filesystem->write($path, $content, true);
    }

    protected function getFilePath(string $type, string $code): string
    {
        return sprintf('%s/%s', $type, basename($code));
    }

    protected function initAttributesList()
    {
        if (empty($this->attributes)) {
            foreach ($this->getAttributes(self::PAGE_SIZE) as $attribute) {
                if (null === $attribute) {
                    continue;
                }

                $this->attributes[$attribute['code']] = $attribute;
            }
        }
    }

    protected function initFamilyVariants()
    {
        if (empty($this->familyVariants)) {
            foreach ($this->client->getFamilyApi()->all(self::PAGE_SIZE) as $family) {
                foreach ($this->client->getFamilyVariantApi()->all($family['code'], self::PAGE_SIZE) as $variant) {
                    $variant['family'] = $family['code'];
                    $this->familyVariants[$variant['code']] = $variant;
                }
            }
        }
    }
}
