<?php
namespace MageMonk\SearchDesign\Test\Unit\Model;

use MageMonk\SearchDesign\Model\CategoryProvider;
use MageMonk\SearchDesign\Model\ProductProvider;
use MageMonk\SearchDesign\Model\SuggestDataProvider;
use MageMonk\SearchDesign\Model\SuggestionProvider;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Search\Helper\Data as SearchHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SuggestDataProviderTest extends TestCase
{
    /**
     * @var SuggestionProvider|MockObject
     */
    private $suggestionProvider;

    /**
     * @var CategoryProvider|MockObject
     */
    private $categoryProvider;

    /**
     * @var ProductProvider|MockObject
     */
    private $productProvider;

    /**
     * @var SearchHelper|MockObject
     */
    private $searchHelper;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var FormKey|MockObject
     */
    private $formKey;

    /**
     * @var SuggestDataProvider
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->suggestionProvider = $this->createMock(SuggestionProvider::class);
        $this->categoryProvider = $this->createMock(CategoryProvider::class);
        $this->productProvider = $this->createMock(ProductProvider::class);
        $this->searchHelper = $this->createMock(SearchHelper::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->formKey = $this->createMock(FormKey::class);

        $this->model = new SuggestDataProvider(
            $this->suggestionProvider,
            $this->categoryProvider,
            $this->productProvider,
            $this->searchHelper,
            $this->urlBuilder,
            $this->formKey
        );
    }

    /**
     * @return void
     */
    public function testGetDataBuildsExpectedPayload(): void
    {
        $query = 'track';
        $suggestions = [['text' => 'track', 'num_results' => null]];
        $categories = [['name' => 'Track', 'url' => '/track', 'parent_name' => 'Default']];
        $products = [['id' => 1, 'name' => 'Track Pant']];

        $this->suggestionProvider->expects($this->once())
            ->method('getSuggestions')
            ->with($query)
            ->willReturn($suggestions);
        $this->categoryProvider->expects($this->once())
            ->method('getCategories')
            ->with($query)
            ->willReturn($categories);
        $this->productProvider->expects($this->once())
            ->method('getProducts')
            ->with($query)
            ->willReturn($products);

        $this->searchHelper->expects($this->exactly(2))
            ->method('getQueryParamName')
            ->willReturn('q');

        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('catalogsearch/result', ['q' => $query])
            ->willReturn('https://magento.test/catalogsearch/result/?q=track');

        $this->formKey->expects($this->once())
            ->method('getFormKey')
            ->willReturn('FORMKEY123');

        $result = $this->model->getData($query);

        $this->assertSame($query, $result['query']);
        $this->assertSame($suggestions, $result['suggestions']);
        $this->assertSame($categories, $result['categories']);
        $this->assertSame($products, $result['products']);
        $this->assertSame('q', $result['query_param']);
        $this->assertSame('FORMKEY123', $result['form_key']);
        $this->assertSame('https://magento.test/catalogsearch/result/?q=track', $result['search_url']);
    }

    /**
     * @return void
     */
    public function testGetDataUsesTrimmedQuery(): void
    {
        $rawQuery = '  shoulder pack  ';
        $trimmedQuery = 'shoulder pack';

        $this->suggestionProvider->expects($this->once())
            ->method('getSuggestions')
            ->with($trimmedQuery)
            ->willReturn([]);
        $this->categoryProvider->expects($this->once())
            ->method('getCategories')
            ->with($trimmedQuery)
            ->willReturn([]);
        $this->productProvider->expects($this->once())
            ->method('getProducts')
            ->with($trimmedQuery)
            ->willReturn([]);
        $this->searchHelper->expects($this->exactly(2))
            ->method('getQueryParamName')
            ->willReturn('q');
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('catalogsearch/result', ['q' => $trimmedQuery])
            ->willReturn('https://magento.test/catalogsearch/result/?q=shoulder+pack');
        $this->formKey->expects($this->once())
            ->method('getFormKey')
            ->willReturn('FORMKEY123');

        $result = $this->model->getData($rawQuery);
        $this->assertSame($trimmedQuery, $result['query']);
    }
}
