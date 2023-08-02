<?php

namespace Japan\Tax\Model;

use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

class CurrencyRoundingFactory
{
    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
    )
    {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->_logger = $logger;
    }

    public function isCurrencyPrecisionEnabled()
    {
        return $this->moduleManager->isEnabled(
            'CommunityEngineering_CurrencyPrecision'
        );
    }

    public function create()
    {
        if ($this->isCurrencyPrecisionEnabled()) {
            $instanceName = 'CommunityEngineering\CurrencyPrecision\Model\CurrencyRounding';
            $this->_logger->debug(
                sprintf('CurrencyPrecision plugin is enabled. Existing round configs will be used.')
            );
        } else {
            $instanceName = 'Japan\Tax\Model\CurrencyRounding';
            $this->_logger->debug(
                sprintf('CurrencyPrecision plugin is not enabled. Default round config will be used.')
            );
        }
        return $this->objectManager->create($instanceName);
    }
}