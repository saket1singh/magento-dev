<?php
namespace MageMonk\SearchDesign\Test\Unit\Model;

use ArrayIterator;
use IteratorAggregate;
use MageMonk\SearchDesign\Model\CategoryProvider;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryProviderTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CategoryProvider
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->categoryCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->model = new CategoryProvider($this->categoryCollectionFactory, $this->storeManager);
    }

    /**
     * @return void
     */
    public function testGetCategoriesReturnsMappedData(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);

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
            public function setStoreId(...$args): self
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
            public function addAttributeToFilter(...$args): self
            {
                return $this;
            }

            /**
             * @return $this
             */
            public function addAttributeToSort(...$args): self
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

        $parent = new class {
            /**
             * @return int
             */
            public function getId(): int
            {
                return 5;
            }

            /**
             * @return string
             */
            public function getName(): string
            {
                return 'Default Category';
            }
        };
        $category = new class ($parent) {
            /**
             * @var object
             */
            private $parent;

            /**
             * @param object $parent
             */
            public function __construct($parent)
            {
                $this->parent = $parent;
            }

            /**
             * @return string
             */
            public function getName(): string
            {
                return 'Training';
            }

            /**
             * @return string
             */
            public function getUrl(): string
            {
                return 'https://magento.test/training.html';
            }

            /**
             * @return object
             */
            public function getParentCategory()
            {
                return $this->parent;
            }
        };

        $collection->seed([$category]);
        $this->categoryCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $result = $this->model->getCategories('train');

        $this->assertCount(1, $result);
        $this->assertSame('Training', $result[0]['name']);
        $this->assertSame('Default Category', $result[0]['parent_name']);
    }
}
