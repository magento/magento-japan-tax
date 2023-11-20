<?php
/**
 * This file is part of the Japanese Consumption Tax Extension For Magento2 project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax\Block\Order;

class StoreInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Store\Model\Information
     */
    protected $_storeInfo;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magentoj\JapaneseConsumptionTax\Model\Config\JctSystemConfig
     */
    protected $_jctConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\Information $storeInfo,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magentoj\JapaneseConsumptionTax\Model\Config\JctSystemConfig $jctConfig,
        array $data = [],
    ) {
        $this->_storeInfo = $storeInfo;
        $this->_storeManager = $storeManager;
        $this->_jctConfig = $jctConfig;
        parent::__construct($context, $data);
    }

    public function getAddress()
    {
        return $this->_storeInfo->getFormattedAddress($this->_storeManager->getStore());
    }

    public function getRegistrationNumber()
    {
        return $this->_jctConfig->getRegistrationNumber($this->_storeManager->getStore()->getStoreId());
    }
}
