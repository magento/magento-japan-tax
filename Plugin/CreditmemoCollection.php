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

class CreditmemoCollection extends AddJctToSalesCreditmemo
{
    public function afterGetItems(
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $subject,
        array $result
    ) {
        $creditmemos = [];
        foreach ($result as $creditmemo) {
            $creditmemoExtension = $creditmemo->getExtensionAttributes();
            $creditmemos[] = $creditmemoExtension->getJctTotals() ?
                $creditmemo : $this->addJctToCreditmemo($creditmemo);
        }
        return $creditmemos;
    }
}
