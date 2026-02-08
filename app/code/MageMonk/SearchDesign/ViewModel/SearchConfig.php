<?php
namespace MageMonk\SearchDesign\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageMonk\SearchDesign\Model\Config;

class SearchConfig implements ArgumentInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /** @var SearchHelper */
    private $searchHelper;

    /** @var Config */
    private $config;

    /**
     * Build suggestion config view model.
     *
     * @param UrlInterface $urlBuilder
     * @param SearchHelper $searchHelper
     * @param Config $config
     */
    public function __construct(
        UrlInterface $urlBuilder,
        SearchHelper $searchHelper,
        Config $config
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->searchHelper = $searchHelper;
        $this->config = $config;
    }

    /**
     * Return AJAX endpoint for suggestion data.
     *
     * @return string
     */
    public function getSuggestUrl(): string
    {
        return $this->urlBuilder->getUrl('searchdesign/ajax/suggest');
    }

    /**
     * Return search query param key.
     *
     * @return string
     */
    public function getQueryParamName(): string
    {
        return $this->searchHelper->getQueryParamName();
    }

    /**
     * Return whether custom search design is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
