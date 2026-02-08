<?php
namespace MageMonk\SearchDesign\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use MageMonk\SearchDesign\Model\Config;
use MageMonk\SearchDesign\Model\SuggestDataProvider;

class Suggest extends Action implements HttpGetActionInterface
{
    /**
     * @var SuggestDataProvider
     */
    private $dataProvider;

    /** @var Config */
    private $config;

    public function __construct(
        Context $context,
        Config $config,
        SuggestDataProvider $dataProvider
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->dataProvider = $dataProvider;
    }

    public function execute()
    {
        if (!$this->config->isEnabled()) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_url->getBaseUrl());
            return $resultRedirect;
        }

        $query = (string) $this->getRequest()->getParam('q', '');
        if (trim($query) === '') {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_url->getBaseUrl());
            return $resultRedirect;
        }

        $data = $this->dataProvider->getData($query);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);
        return $resultJson;
    }
}
