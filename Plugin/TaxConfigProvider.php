<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

class TaxConfigProvider
{
    /**
     * @var TaxHelper
     */
    protected $_taxHelper;

    public function __construct(\Magento\Tax\Helper\Data $taxHelper)
    {
        $this->_taxHelper = $taxHelper;
    }

    public function afterGetConfig(
        \Magento\Tax\Model\TaxConfigProvider $subject,
        array $result,
    ) {
        $result['priceIncludesTax'] = $this->_taxHelper->priceIncludesTax();
        return $result;
    }
}
