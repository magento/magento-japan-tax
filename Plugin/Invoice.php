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

class Invoice extends AddJctToSalesInvoice
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $subject
     * @param \Magento\Sales\Model\Order\Invoice $result
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function afterLoad(
        \Magento\Sales\Model\Order\Invoice $subject,
        \Magento\Sales\Model\Order\Invoice $result
    ) {
        return $this->addJctToInvoice($result);
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $subject
     * @param \Magento\Sales\Model\Order\Invoice $result
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function afterSave(
        \Magento\Sales\Model\Order\Invoice $subject,
        \Magento\Sales\Model\Order\Invoice $result
    ) {
        $model = $this->getSalesInvoiceByInvoiceId($result->getEntityId());

        $jctTotals = $result->getExtensionAttributes()->getJctTotals();

        if ($model->getData() || !$jctTotals) {
            return $result;
        }

        $model->setJctTotals(json_encode($jctTotals->getData()));
        $model->setInvoiceId($result->getEntityId());

        $resourceModel = $this->magentojSalesInvoiceResourceModelFactory->create();
        $resourceModel->save($model);

        return $result;
    }
}
