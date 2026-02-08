<?php
namespace MageMonk\SearchDesign\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageMonk\SearchDesign\Model\Config;

class AddLayoutHandle implements ObserverInterface
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $layout = $observer->getEvent()->getLayout();
        if (!$layout) {
            return;
        }

        $layout->getUpdate()->addHandle('searchdesign_enabled');
    }
}
