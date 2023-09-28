<?php

namespace Magentoj\JapaneseConsumptionTax\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class JctSystemConfig
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    const XML_PATH = 'tax/jct/';

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get registration number
     *
     * @return string
     */
    public function getRegistrationNumber($storeId = null)
    {
        return $this->getConfigValue(
            self::XML_PATH . 'registration_number',
            $storeId
        );
    }

    /**
     * Get store config value
     *
     * @return string
     */
    private function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_WEBSITE,
            $storeId
        );
    }
}
