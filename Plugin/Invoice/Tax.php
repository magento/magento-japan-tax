<?php
namespace Japan\Tax\Plugin\Invoice;

class Tax
{
    public function afterCollect(
        \Magento\Sales\Model\Order\Invoice\Total\Tax $subject,
        \Magento\Sales\Model\Order\Invoice\Total\Tax $result,
        \Magento\Sales\Model\Order\Invoice $invoice,
    ) {
        $order = $invoice->getOrder();

        $invoice->setSubtotalExclJct10($order->getSubtotalExclJct10());
        $invoice->setSubtotalInclJct10($order->getSubtotalInclJct10());
        $invoice->setSubtotalExclJct8($order->getSubtotalExclJct8());
        $invoice->setSubtotalInclJct8($order->getSubtotalInclJct8());
        $invoice->setJct10Amount($order->getJct10Amount());
        $invoice->setJct8Amount($order->getJct8Amount());

        return $result;
    }
}