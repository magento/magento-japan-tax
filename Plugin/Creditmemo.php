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

class Creditmemo extends AddJctToSalesCreditmemo
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $subject
     * @param \Magento\Sales\Model\Order\Creditmemo $result
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function afterLoad(
        \Magento\Sales\Model\Order\Creditmemo $subject,
        \Magento\Sales\Model\Order\Creditmemo $result
    ) {
        return $this->addJctToCreditmemo($result);
    }
}
