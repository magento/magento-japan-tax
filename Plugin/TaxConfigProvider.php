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
