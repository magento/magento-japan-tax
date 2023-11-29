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
use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;

class Tax extends \Magento\Sales\Model\Order\Invoice\Total\Tax
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalsSetupTrait;

    public const JCT_10_PERCENT = 10.0;
    public const JCT_8_PERCENT = 8.0;

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
                self::JCT_10_PERCENT,
                new OrderItemAdapter($shippingItem),
            );
        }

        $blocks = [];
        if ($isTaxIncluded) {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $invoice->getOrderCurrencyCode()
                );
            }
        } else {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $invoice->getOrderCurrencyCode()
                );
            }
        }

        $jctTotals = [];
        $totalTax = 0;
        foreach ($blocks as $block) {
            $totalTax += $block->getTax();

            $taxPercent = $block->getTaxPercent();
            if ($taxPercent === self::JCT_10_PERCENT || $taxPercent === self::JCT_8_PERCENT) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block);
            }
        }

        $invoice->setTaxAmount($totalTax);

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
