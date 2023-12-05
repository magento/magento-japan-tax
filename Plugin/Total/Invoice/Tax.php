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
namespace Magentoj\JapaneseConsumptionTax\Plugin\Total\Invoice;

use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Constants;
use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;

class Tax extends \Magento\Sales\Model\Order\Invoice\Total\Tax
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalsSetupTrait;

    /**
     * @var \Magento\Sales\Model\Order\Invoice\ItemFactory
     */
    protected $invoiceItemFactory;

    /**
     * @var \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    /**
     * @var JctTotalsInterfaceFactory
     */
    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    public function __construct(
        \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory,
        \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->invoiceItemFactory = $invoiceItemFactory;
        $this->jctTaxCalculator = $jctTaxCalculator;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
        parent::__construct();
    }

    public function afterCollect(
        \Magento\Sales\Model\Order\Invoice\Total\Tax $subject,
        \Magento\Sales\Model\Order\Invoice\Total\Tax $result,
        \Magento\Sales\Model\Order\Invoice $invoice,
    ) {
        $order = $invoice->getOrder();
        $orderExtension = $order->getExtensionAttributes();
        $jctTotals = $orderExtension->getJctTotals();
        $isTaxIncluded = $jctTotals->getIsTaxIncluded();

        $aggregate = [];
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy() || $item->getQty() <= 0) {
                continue;
            }

            $aggregate = $this->updateItemAggregate(
                $aggregate,
                (float)$item->getTaxPercent(),
                new OrderItemAdapter($item)
            );
        }

        if ($this->_canIncludeShipping($invoice)) {
            $shippingItem = $this->invoiceItemFactory->create();
            $shippingItem
                ->setProductType('shipping')
                ->setSku('shipping')
                ->setPrice($isTaxIncluded ?
                    $invoice->getShippingInclTax() : $invoice->getShippingAmount())
                ->setQty(1);
            $aggregate = $this->updateItemAggregate(
                $aggregate,
                Constants::JCT_10_PERCENT,
                new OrderItemAdapter($shippingItem),
            );
        }

        $baseBlocks = $this->getJctBlocks(
            $this->jctTaxCalculator,
            $aggregate,
            $isTaxIncluded,
            $invoice->getBaseCurrencyCode(),
        );

        $blocks = $this->getJctBlocks(
            $this->jctTaxCalculator,
            $aggregate,
            $isTaxIncluded,
            $invoice->getStoreCurrencyCode(),
        );

        $jctTotals = $this->getJctTotalsArray($baseBlocks, $blocks);

        $getTotalTaxFn = fn($carry, $item) => $carry + $item->getTax();
        $invoice->setBaseTaxAmount(array_reduce($baseBlocks, $getTotalTaxFn));
        $invoice->setTaxAmount(array_reduce($blocks, $getTotalTaxFn));

        $invoiceExtension = $invoice->getExtensionAttributes();
        $invoiceExtension->setJctTotals(
            $this->jctTotalsInterfaceFactory->create(
                [
                    'data' => $jctTotals
                ]
            )
        );
        $invoice->setExtensionAttributes($invoiceExtension);

        return $result;
    }
}
