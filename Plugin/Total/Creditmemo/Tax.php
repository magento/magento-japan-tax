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
use Magentoj\JapaneseConsumptionTax\Constants;
use Magentoj\JapaneseConsumptionTax\Model\Calculation\OrderItemAdapter;

class Tax
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalsSetupTrait;

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
        $isTaxIncluded = $jctTotals->getIsTaxIncluded();

        $aggregate = [];
        foreach ($creditmemo->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy() || $item->getQty() <= 0) {
                continue;
            }
            $aggregate = $this->updateItemAggregate(
                $aggregate,
                (float)$item->getTaxPercent(),
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
            Constants::JCT_10_PERCENT,
            new OrderItemAdapter($shippingItem),
        );

        $baseBlocks = $this->getJctBlocks(
            $this->jctTaxCalculator,
            $aggregate,
            $isTaxIncluded,
            $creditmemo->getBaseCurrencyCode()
        );

        $blocks = $this->getJctBlocks(
            $this->jctTaxCalculator,
            $aggregate,
            $isTaxIncluded,
            $creditmemo->getStoreCurrencyCode()
        );

        $jctTotals = $this->getJctTotalsArray($baseBlocks, $blocks);

        $getTotalTaxFn = fn($carry, $item) => $carry + $item->getTax();
        $creditmemo->setBaseTaxAmount(array_reduce($baseBlocks, $getTotalTaxFn));
        $creditmemo->setTaxAmount(array_reduce($blocks, $getTotalTaxFn));

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
