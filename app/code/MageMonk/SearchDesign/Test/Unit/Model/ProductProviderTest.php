<?php
namespace MageMonk\SearchDesign\Test\Unit\Model;

use ArrayIterator;
use IteratorAggregate;
use MageMonk\SearchDesign\Model\ProductProvider;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductProviderTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var Visibility|MockObject
     */
    private $visibility;

    /**
     * @var ImageHelper|MockObject
     */
    private $imageHelper;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Render|MockObject
     */
    private $priceRender;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var EncoderInterface|MockObject
     */
    private $urlEncoder;

    /**
     * @var ProductProvider
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->visibility = $this->createMock(Visibility::class);
        $this->imageHelper = $this->createMock(ImageHelper::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->priceRender = $this->createMock(Render::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->urlEncoder = $this->createMock(EncoderInterface::class);

        $this->model = new ProductProvider(
            $this->collectionFactory,
            $this->visibility,
            $this->imageHelper,
            $this->storeManager,
            $this->priceRender,
            $this->urlBuilder,
            $this->urlEncoder
        );
    }

    /**
     * @return void
     */
    public function testGetProductsReturnsMappedProductPayload(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $this->visibility->expects($this->once())->method('getVisibleInSearchIds')->willReturn([3, 4]);

        $collection = new class implements IteratorAggregate {
            /**
             * @var array
             */
            private $items = [];

            /**
             * @param array $items
             * @return $this
             */
            public function seed(array $items): self
            {
                $this->items = $items;
                return $this;
            }

            /**
             * @return $this
             */
            public function addStoreFilter(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function addAttributeToSelect(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function addSearchFilter(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function addAttributeToFilter(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function setVisibility(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function setPageSize(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function setCurPage(...$args): self
            {
                return $this;
            }

            /**
             * @return ArrayIterator
             */
            public function getIterator(): ArrayIterator
            {
                return new ArrayIterator($this->items);
            }
        };

        $typeInstance = new class {
            /**
             * @return bool
             */
            public function hasRequiredOptions(...$args): bool
            {
                return false;
            }
        };

        $product = $this->createPartialMock(
            Product::class,
            ['isSaleable', 'getTypeInstance', 'getId', 'getName', 'getSku', 'getProductUrl']
        );
        $product->method('isSaleable')->willReturn(true);
        $product->method('getTypeInstance')->willReturn($typeInstance);
        $product->method('getId')->willReturn(2);
        $product->method('getName')->willReturn('Strive Shoulder Pack');
        $product->method('getSku')->willReturn('24-MB04');
        $product->method('getProductUrl')->willReturn('https://magento.test/strive-shoulder-pack.html');

        $collection->seed([$product]);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);

        $this->imageHelper->expects($this->once())->method('init')->with($product, 'product_page_image_small')
            ->willReturnSelf();
        $this->imageHelper->expects($this->once())->method('getUrl')
            ->willReturn('https://magento.test/media/catalog/product/s/h/shoulder.jpg');

        $this->priceRender->expects($this->once())->method('render')
            ->willReturn('<span class="price">$32.00</span>');

        $this->urlEncoder->expects($this->once())->method('encode')
            ->with('https://magento.test/strive-shoulder-pack.html')
            ->willReturn('ENCODED_URL');

        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->with(
                'checkout/cart/add',
                ['product' => 2, 'uenc' => 'ENCODED_URL']
            )
            ->willReturn('https://magento.test/checkout/cart/add/product/2/uenc/ENCODED_URL/');

        $result = $this->model->getProducts('shoulder pack');

        $this->assertCount(1, $result);
        $this->assertSame('Strive Shoulder Pack', $result[0]['name']);
        $this->assertSame(true, $result[0]['can_add_to_cart']);
        $this->assertSame('https://magento.test/checkout/cart/add/product/2/uenc/ENCODED_URL/', $result[0]['add_to_cart_url']);
    }

    /**
     * @return void
     */
    public function testGetProductsHandlesPriceRenderException(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->visibility->method('getVisibleInSearchIds')->willReturn([3, 4]);

        $collection = new class implements IteratorAggregate {
            /**
             * @var array
             */
            private $items = [];

            /**
             * @param array $items
             * @return $this
             */
            public function seed(array $items): self
            {
                $this->items = $items;
                return $this;
            }
            public function addStoreFilter(...$args): self { return $this; }
            public function addAttributeToSelect(...$args): self { return $this; }
            public function addSearchFilter(...$args): self { return $this; }
            public function addAttributeToFilter(...$args): self { return $this; }
            public function setVisibility(...$args): self { return $this; }
            public function setPageSize(...$args): self { return $this; }
            public function setCurPage(...$args): self { return $this; }
            public function getIterator(): ArrayIterator { return new ArrayIterator($this->items); }
        };

        $typeInstance = new class {
            /**
             * @return bool
             */
            public function hasRequiredOptions(...$args): bool
            {
                return true;
            }
        };

        $product = $this->createPartialMock(
            Product::class,
            ['isSaleable', 'getTypeInstance', 'getId', 'getName', 'getSku', 'getProductUrl']
        );
        $product->method('isSaleable')->willReturn(true);
        $product->method('getTypeInstance')->willReturn($typeInstance);
        $product->method('getId')->willReturn(10);
        $product->method('getName')->willReturn('Compete Track Tote');
        $product->method('getSku')->willReturn('24-WB02');
        $product->method('getProductUrl')->willReturn('https://magento.test/compete-track-tote.html');

        $collection->seed([$product]);
        $this->collectionFactory->method('create')->willReturn($collection);
        $this->imageHelper->method('init')->willReturnSelf();
        $this->imageHelper->method('getUrl')->willReturn('https://magento.test/image.jpg');
        $this->urlEncoder->method('encode')->willReturn('ENCODED_URL');
        $this->urlBuilder->method('getUrl')->willReturn('https://magento.test/add');
        $this->priceRender->method('render')->willThrowException(new \RuntimeException('render failed'));

        $result = $this->model->getProducts('track');

        $this->assertSame('', $result[0]['price_html']);
        $this->assertFalse($result[0]['can_add_to_cart']);
    }
}
