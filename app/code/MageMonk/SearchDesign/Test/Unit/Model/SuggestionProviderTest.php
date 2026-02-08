<?php
namespace MageMonk\SearchDesign\Test\Unit\Model;

use MageMonk\SearchDesign\Model\SuggestionProvider;
use Magento\Search\Model\AutocompleteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SuggestionProviderTest extends TestCase
{
    /**
     * @var AutocompleteInterface|MockObject
     */
    private $autocomplete;

    /**
     * @var SuggestionProvider
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->autocomplete = $this->createMock(AutocompleteInterface::class);
        $this->model = new SuggestionProvider($this->autocomplete);
    }

    /**
     * @return void
     */
    public function testGetSuggestionsAddsTypedQueryWhenMissing(): void
    {
        $item = new class {
            /**
             * @return array
             */
            public function toArray(): array
            {
                return ['title' => 'tank', 'num_results' => 3];
            }
        };

        $this->autocomplete->expects($this->once())
            ->method('getItems')
            ->willReturn([$item]);

        $result = $this->model->getSuggestions('track');

        $this->assertSame('track', $result[0]['text']);
        $this->assertSame('tank', $result[1]['text']);
    }

    /**
     * @return void
     */
    public function testGetSuggestionsDoesNotDuplicateExactQuery(): void
    {
        $item = new class {
            /**
             * @return array
             */
            public function toArray(): array
            {
                return ['title' => 'Track', 'num_results' => null];
            }
        };

        $this->autocomplete->expects($this->once())
            ->method('getItems')
            ->willReturn([$item]);

        $result = $this->model->getSuggestions('track');

        $this->assertCount(1, $result);
        $this->assertSame('Track', $result[0]['text']);
    }
}
