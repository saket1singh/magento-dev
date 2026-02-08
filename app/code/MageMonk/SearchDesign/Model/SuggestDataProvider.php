<?php
namespace MageMonk\SearchDesign\Model;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Render;
use Magento\Catalog\Model\Product;

class SuggestDataProvider
{
    private const CATEGORY_LIMIT = 6;
    private const PRODUCT_LIMIT = 3;

    /** @var AutocompleteInterface */
    private $autocomplete;

    /** @var CategoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var ProductCollectionFactory */
    private $fulltextCollectionFactory;

    /** @var Visibility */
    private $productVisibility;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var SearchHelper */
    private $searchHelper;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var FormKey */
    private $formKey;

    /** @var Render */
    private $priceRender;

    /** @var EncoderInterface */
    private $urlEncoder;

    /**
     * Build suggestion data provider.
     *
     * @param AutocompleteInterface $autocomplete
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $fulltextCollectionFactory
     * @param Visibility $productVisibility
     * @param ImageHelper $imageHelper
     * @param SearchHelper $searchHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param FormKey $formKey
     * @param Render $priceRender
     * @param EncoderInterface $urlEncoder
     */
    public function __construct(
        AutocompleteInterface $autocomplete,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $fulltextCollectionFactory,
        Visibility $productVisibility,
        ImageHelper $imageHelper,
        SearchHelper $searchHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        FormKey $formKey,
        Render $priceRender,
        EncoderInterface $urlEncoder
    ) {
        $this->autocomplete = $autocomplete;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->fulltextCollectionFactory = $fulltextCollectionFactory;
        $this->productVisibility = $productVisibility;
        $this->imageHelper = $imageHelper;
        $this->searchHelper = $searchHelper;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->formKey = $formKey;
        $this->priceRender = $priceRender;
        $this->urlEncoder = $urlEncoder;
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
            'suggestions' => $this->getSuggestions($query),
            'categories' => $this->getCategories($query),
            'products' => $this->getProducts($query),
            'search_url' => $this->getSearchUrl($query),
            'query_param' => $this->searchHelper->getQueryParamName(),
            'form_key' => $this->formKey->getFormKey()
        ];
    }

    /**
     * Collect suggested search terms.
     *
     * @param string $query
     *
     * @return array
     */
    private function getSuggestions(string $query): array
    {
        $items = [];
        foreach ($this->autocomplete->getItems() as $item) {
            $data = $item->toArray();
            if (!empty($data['title'])) {
                $items[] = [
                    'text' => $data['title'],
                    'num_results' => $data['num_results'] ?? null
                ];
            }
        }

        // Ensure exact query appears first if not already
        if ($query !== '') {
            $exists = false;
            foreach ($items as $item) {
                if (mb_strtolower($item['text']) === mb_strtolower($query)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                array_unshift($items, [
                    'text' => $query,
                    'num_results' => null
                ]);
            }
        }

        return $items;
    }

    /**
     * Collect matching categories.
     *
     * @param string $query
     *
     * @return array
     */
    private function getCategories(string $query): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('name', ['like' => '%' . $query . '%'])
            ->addAttributeToSort('name', 'ASC')
            ->setPageSize(self::CATEGORY_LIMIT)
            ->setCurPage(1);

        $items = [];
        foreach ($collection as $category) {
            $parentName = '';
            try {
                $parent = $category->getParentCategory();
                if ($parent && $parent->getId()) {
                    $parentName = (string) $parent->getName();
                }
            } catch (\Exception $e) {
                $parentName = '';
            }

            $items[] = [
                'name' => (string) $category->getName(),
                'url' => (string) $category->getUrl(),
                'parent_name' => $parentName
            ];
        }

        return $items;
    }

    /**
     * Collect matching products from fulltext collection.
     *
     * @param string $query
     *
     * @return array
     */
    private function getProducts(string $query): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection = $this->fulltextCollectionFactory->create();
        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect(['name', 'sku', 'small_image'])
            ->addSearchFilter($query)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->setVisibility($this->productVisibility->getVisibleInSearchIds())
            ->setPageSize(self::PRODUCT_LIMIT)
            ->setCurPage(1);

        $items = [];
        foreach ($collection as $product) {
            $canAddToCart = $product->isSaleable() && !$product->getTypeInstance()->hasRequiredOptions($product);
            $items[] = [
                'id' => (int) $product->getId(),
                'name' => (string) $product->getName(),
                'sku' => (string) $product->getSku(),
                'url' => (string) $product->getProductUrl(),
                'image' => (string) $this->imageHelper
                    ->init($product, 'product_page_image_small')
                    ->getUrl(),
                'add_to_cart_url' => $this->getAddToCartUrl($product),
                'can_add_to_cart' => $canAddToCart,
                'price_html' => $this->getPriceHtml($product)
            ];
        }

        return $items;
    }

    /**
     * Render product price HTML snippet.
     *
     * @param Product $product
     *
     * @return string
     */
    private function getPriceHtml(Product $product): string
    {
        try {
            return (string) $this->priceRender->render(
                'final_price',
                $product,
                [
                    'zone' => Render::ZONE_ITEM_LIST,
                    'price_id' => 'searchdesign-price-' . (int) $product->getId(),
                    'include_container' => true,
                    'display_minimal_price' => true
                ]
            );
        } catch (\Throwable $e) {
            return '';
        }
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

    /**
     * Build add-to-cart URL with safe return target.
     *
     * @param Product $product
     *
     * @return string
     */
    private function getAddToCartUrl(Product $product): string
    {
        return $this->urlBuilder->getUrl(
            'checkout/cart/add',
            [
                'product' => (int) $product->getId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($product->getProductUrl())
            ]
        );
    }
}
