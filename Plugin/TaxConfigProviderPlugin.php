<?php
namespace Magentoj\JapaneseConsumptionTax\Plugin;

use Magento\Tax\Model\TaxConfigProvider;

class TaxConfigProviderPlugin
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
        TaxConfigProvider $subject,
        array $result,
    ) {
        $result['priceIncludesTax'] = $this->_taxHelper->priceIncludesTax();
        return $result;
    }
}
