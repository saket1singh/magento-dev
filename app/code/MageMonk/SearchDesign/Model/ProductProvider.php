<?php
namespace MageMonk\SearchDesign\Model;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductProvider
{
    private const PRODUCT_LIMIT = 3;

    /** @var CollectionFactory */
    private $fulltextCollection;

    /** @var Visibility */
    private $productVisibility;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Render */
    private $priceRender;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var EncoderInterface */
    private $urlEncoder;

    /**
     * @param CollectionFactory $fulltextCollection
     * @param Visibility $productVisibility
     * @param ImageHelper $imageHelper
     * @param StoreManagerInterface $storeManager
     * @param Render $priceRender
     * @param UrlInterface $urlBuilder
     * @param EncoderInterface $urlEncoder
     */
    public function __construct(
        CollectionFactory $fulltextCollection,
        Visibility $productVisibility,
        ImageHelper $imageHelper,
        StoreManagerInterface $storeManager,
        Render $priceRender,
        UrlInterface $urlBuilder,
        EncoderInterface $urlEncoder
    ) {
        $this->fulltextCollection = $fulltextCollection;
        $this->productVisibility = $productVisibility;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->priceRender = $priceRender;
        $this->urlBuilder = $urlBuilder;
        $this->urlEncoder = $urlEncoder;
    }

    /**
     * Collect matching products from fulltext collection.
     *
     * @param string $query
     *
     * @return array
     */
    public function getProducts(string $query): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection = $this->fulltextCollection->create();
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
