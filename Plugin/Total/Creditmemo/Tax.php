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
namespace Magentoj\JapaneseConsumptionTax\Plugin\Total\Creditmemo;

use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;

class Tax
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalsSetupTrait;

    public const JCT_10_PERCENT = 10;
    public const JCT_8_PERCENT = 8;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo\ItemFactory
     */
    protected $creditmemoItemFactory;

    /**
     * @var \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    /**
     * @var JctTotalsInterfaceFactory
     */
    private JctTotalsInterfaceFactory $jctTotalsInterfaceFactory;

    public function __construct(
        \Magento\Sales\Model\Order\Creditmemo\ItemFactory $creditmemoItemFactory,
        \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->creditmemoItemFactory = $creditmemoItemFactory;
        $this->jctTaxCalculator = $jctTaxCalculator;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
    }

    public function afterCollect(
        \Magento\Sales\Model\Order\Creditmemo\Total\Tax $subject,
        \Magento\Sales\Model\Order\Creditmemo\Total\Tax $result,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
    ) {
        $order = $creditmemo->getOrder();
        $orderExtension = $order->getExtensionAttributes();
        $jctTotals = $orderExtension->getJctTotals();
        $isTaxIncluded = $jctTotals['is_tax_included'] ?? null;

        $aggregate = [];
        foreach ($creditmemo->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy() || $item->getQty() <= 0) {
                continue;
            }
            $aggregate = $this->updateItemAggregate(
                $aggregate,
                (int)$item->getTaxPercent(),
                new OrderItemAdapter($item)
            );
        }

        $shippingItem = $this->creditmemoItemFactory->create();
        $shippingItem
            ->setProductType('shipping')
            ->setSku('shipping')
            ->setPrice($isTaxIncluded ?
                    $creditmemo->getShippingInclTax() : $creditmemo->getShippingAmount())
            ->setQty(1);
        $aggregate = $this->updateItemAggregate(
            $aggregate,
            self::JCT_10_PERCENT,
            new OrderItemAdapter($shippingItem),
        );

        $blocks = [];
        if ($isTaxIncluded) {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $creditmemo->getOrderCurrencyCode()
                );
            }
        } else {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $creditmemo->getOrderCurrencyCode()
                );
            }
        }

        $jctTotals = [];
        $totalTax = 0;
        foreach ($blocks as $block) {
            $totalTax += $block->getTax();

            $taxPercent = (int)$block->getTaxPercent();
            if ($taxPercent === self::JCT_10_PERCENT) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block);
            } elseif ($taxPercent === self::JCT_8_PERCENT) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block);
            }
        }

        $creditmemo->setTaxAmount($totalTax);

        $creditmemoExtension = $creditmemo->getExtensionAttributes();
        $creditmemoExtension->setJctTotals(
            $this->jctTotalsInterfaceFactory->create(
                [
                    'data' => $jctTotals
                ]
            )
        );
        $creditmemo->setExtensionAttributes($creditmemoExtension);

        return $result;
    }
}
