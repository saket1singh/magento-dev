<?php
namespace MageMonk\SearchDesign\Test\Unit\Controller\Ajax;

use MageMonk\SearchDesign\Controller\Ajax\Suggest;
use MageMonk\SearchDesign\Model\Config;
use MageMonk\SearchDesign\Model\SuggestDataProvider;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SuggestTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var SuggestDataProvider|MockObject
     */
    private $dataProvider;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->dataProvider = $this->createMock(SuggestDataProvider::class);
        $this->context = $this->createMock(Context::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);

        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getResultFactory')->willReturn($this->resultFactory);
        $this->context->method('getUrl')->willReturn($this->urlBuilder);
    }

    /**
     * @return Suggest
     */
    private function createController(): Suggest
    {
        return new Suggest($this->context, $this->config, $this->dataProvider);
    }

    /**
     * @return void
     */
    public function testExecuteRedirectsWhenModuleDisabled(): void
    {
        $redirectResult = $this->createMock(Redirect::class);

        $this->config->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirectResult);
        $this->urlBuilder->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://magento.test/');
        $redirectResult->expects($this->once())
            ->method('setUrl')
            ->with('https://magento.test/')
            ->willReturnSelf();

        $this->request->expects($this->never())->method('getParam');
        $this->dataProvider->expects($this->never())->method('getData');

        $this->assertSame($redirectResult, $this->createController()->execute());
    }

    /**
     * @return void
     */
    public function testExecuteRedirectsWhenQueryIsEmpty(): void
    {
        $redirectResult = $this->createMock(Redirect::class);

        $this->config->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('q', '')
            ->willReturn('   ');
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirectResult);
        $this->urlBuilder->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://magento.test/');
        $redirectResult->expects($this->once())
            ->method('setUrl')
            ->with('https://magento.test/')
            ->willReturnSelf();
        $this->dataProvider->expects($this->never())->method('getData');

        $this->assertSame($redirectResult, $this->createController()->execute());
    }

    /**
     * @return void
     */
    public function testExecuteReturnsJsonForValidQuery(): void
    {
        $jsonResult = $this->createMock(Json::class);
        $payload = ['query' => 'tank', 'products' => []];

        $this->config->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('q', '')
            ->willReturn('tank');
        $this->dataProvider->expects($this->once())
            ->method('getData')
            ->with('tank')
            ->willReturn($payload);
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($jsonResult);
        $jsonResult->expects($this->once())
            ->method('setData')
            ->with($payload)
            ->willReturnSelf();

        $this->assertSame($jsonResult, $this->createController()->execute());
    }
}
