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

    public function __construct(
        UrlInterface $urlBuilder,
        SearchHelper $searchHelper,
        Config $config
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->searchHelper = $searchHelper;
        $this->config = $config;
    }

    public function getSuggestUrl(): string
    {
        return $this->urlBuilder->getUrl('searchdesign/ajax/suggest');
    }

    public function getQueryParamName(): string
    {
        return $this->searchHelper->getQueryParamName();
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
