<?php
/**
 * This file is part of Japanese Consumption Tax Extension For Magento2 the  project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Model;

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
    ) {
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
            $instanceName = \CommunityEngineering\CurrencyPrecision\Model\CurrencyRounding::class;
            $this->_logger->debug(
                sprintf('CurrencyPrecision plugin is enabled. Existing round configs will be used.')
            );
        } else {
            $instanceName = \Magentoj\JapaneseConsumptionTax\Model\CurrencyRounding::class;
            $this->_logger->debug(
                sprintf('CurrencyPrecision plugin is not enabled. Default round config will be used.')
            );
        }
        return $this->objectManager->create($instanceName);
    }
}
