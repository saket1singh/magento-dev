<?php
namespace MageMonk\SearchDesign\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class CategoryProvider
{
    private const CATEGORY_LIMIT = 6;

    /** @var CollectionFactory */
    private $categoryCollection;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param CollectionFactory $categoryCollection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $categoryCollection,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->storeManager = $storeManager;
    }

    /**
     * Collect matching categories.
     *
     * @param string $query
     *
     * @return array
     */
    public function getCategories(string $query): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection = $this->categoryCollection->create();
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
}
