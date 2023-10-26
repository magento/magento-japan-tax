<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin;

class Invoice extends AddJctToSalesInvoice
{
    /**
     * @param \Magento\Sales\Model\åOrder\Invoice $subject
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
