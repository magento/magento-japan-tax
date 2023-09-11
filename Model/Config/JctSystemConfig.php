<?php

namespace Japan\Tax\Model\Config;

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

    public function getRegistrationNumber()
    {
        return $this->getConfigValue(
            self::XML_PATH . 'registration_number'
        );
    }

    private function getConfigValue(string $field)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
