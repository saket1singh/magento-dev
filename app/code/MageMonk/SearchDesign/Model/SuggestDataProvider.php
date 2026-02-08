<?php
namespace MageMonk\SearchDesign\Model;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Search\Helper\Data as SearchHelper;

class SuggestDataProvider
{
    /** @var SearchHelper */
    private $searchHelper;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var FormKey */
    private $formKey;

    /** @var SuggestionProvider */
    private $suggestionProvider;

    /** @var CategoryProvider */
    private $categoryProvider;

    /** @var ProductProvider */
    private $productProvider;

    /**
     * Build suggestion data provider.
     *
     * @param SuggestionProvider $suggestionProvider
     * @param CategoryProvider $categoryProvider
     * @param ProductProvider $productProvider
     * @param SearchHelper $searchHelper
     * @param UrlInterface $urlBuilder
     * @param FormKey $formKey
     */
    public function __construct(
        SuggestionProvider $suggestionProvider,
        CategoryProvider $categoryProvider,
        ProductProvider $productProvider,
        SearchHelper $searchHelper,
        UrlInterface $urlBuilder,
        FormKey $formKey
    ) {
        $this->suggestionProvider = $suggestionProvider;
        $this->categoryProvider = $categoryProvider;
        $this->productProvider = $productProvider;
        $this->searchHelper = $searchHelper;
        $this->urlBuilder = $urlBuilder;
        $this->formKey = $formKey;
    }

    /**
     * Build payload for autocomplete endpoint.
     *
     * @param string $query
     *
     * @return array
     */
    public function getData(string $query): array
    {
        $query = trim($query);

        return [
            'query' => $query,
            'suggestions' => $this->suggestionProvider->getSuggestions($query),
            'categories' => $this->categoryProvider->getCategories($query),
            'products' => $this->productProvider->getProducts($query),
            'search_url' => $this->getSearchUrl($query),
            'query_param' => $this->searchHelper->getQueryParamName(),
            'form_key' => $this->formKey->getFormKey()
        ];
    }

    /**
     * Build storefront search result URL.
     *
     * @param string $query
     *
     * @return string
     */
    private function getSearchUrl(string $query): string
    {
        return $this->urlBuilder->getUrl(
            'catalogsearch/result',
            [$this->searchHelper->getQueryParamName() => $query]
        );
    }
}
