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
namespace Magentoj\JapaneseConsumptionTax\Model\Calculation;

use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxBlockInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxItemInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Model\CurrencyRoundingFactory;

/**
 * Japan Consumption Tax calculator.
 */
class JctTaxCalculator
{
    /**#@+
     * Constants for delta rounding key
     */
    const KEY_REGULAR_DELTA_ROUNDING = 'regular';

    const KEY_APPLIED_TAX_DELTA_ROUNDING = 'applied_tax_amount';

    const KEY_TAX_BEFORE_DISCOUNT_DELTA_ROUNDING = 'tax_before_discount';

    /**
     * Tax configuration object
     *
     * @var \Magento\Tax\Model\Config
     */
    protected $config;

    /**
     * Tax calculation model
     *
     * @var \Magento\Tax\Model\Calculatior
     */
    protected $calculationTool;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory
     */
    protected $appliedTaxDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory
     */
    protected $appliedTaxRateDataObjectFactory;

    /**
     * @var InvoiceTaxBlockInterfaceFactory
     */
    protected $invoiceTaxBlockFactory;

    /**
     * @var InvoiceTaxItemInterfaceFactory
     */
    protected $invoiceTaxItemFactory;

    /**
     * @var CurrencyRoundingFactory
     */
    private $currencyRoundingFactory;

    /**
     * @var array
     */
    private $roundingDeltas = [];

    /**
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Model\Calculation $calculation
     * @param \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory
     * @param \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory
     * @param InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory
     * @param InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory
     * @param CurrencyRoundingFactory $currencyRoundingFactory
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory,
        \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory,
        InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory,
        InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory,
        CurrencyRoundingFactory $currencyRoundingFactory,
    ) {
        $this->config = $taxConfig;
        $this->calculationTool = $calculation;
        $this->appliedTaxDataObjectFactory = $appliedTaxDataObjectFactory;
        $this->appliedTaxRateDataObjectFactory = $appliedTaxRateDataObjectFactory;
        $this->invoiceTaxBlockFactory = $invoiceTaxBlockFactory;
        $this->invoiceTaxItemFactory = $invoiceTaxItemFactory;
        $this->currencyRoundingFactory = $currencyRoundingFactory;
    }

    /**
     * Calculate Tax In Price
     *
     * @param array $items
     * @param int $taxRate
     * @param array $appliedRates
     * @param string $currencyCode
     * @return InvoiceTaxBlockInterface
     */
    public function calculateWithTaxInPrice(array $items, $taxRate, $appliedRates, $currencyCode)
    {
        $currencyRounding = $this->currencyRoundingFactory->create();
        $appliedTaxes = [];
        $blockTax = 0;
        $blockTotalInclTax = 0;
        $invoiceTaxItems = [];
        $blockDiscountAmount = 0;
        $blockTaxableAmount = 0;

        // Compute tax details for each item
        foreach ($items as $item) {
            $quantity = $item->getQuantity();
            $discountAmount = $item->getDiscountAmount();
            $priceInclTax = $item->getUnitPrice();
            $rowTotalInclTax = $priceInclTax * $quantity;
            $rowTax = $this->calculationTool->calcTaxAmount(
                $rowTotalInclTax,
                $taxRate,
                true,
                false
            );
            $rowTaxExact = $currencyRounding->round($currencyCode, $rowTax);
            $rowTotal = $rowTotalInclTax - $rowTaxExact;
            $price = $rowTotal / $quantity;
            $taxableAmount = max($rowTotalInclTax - $discountAmount, 0);

            // Aggregate totals for all items.
            $blockTaxableAmount += $taxableAmount;
            $blockDiscountAmount += $discountAmount;
            $blockTotalInclTax += $rowTotalInclTax;

            $invoiceTaxItems[] = $this->invoiceTaxItemFactory->create()
                ->setPrice($price)
                ->setPriceInclTax($priceInclTax)
                ->setCode($item->getCode())
                ->setType($item->getType())
                ->setTaxPercent($taxRate)
                ->setQuantity($quantity)
                ->setTaxableAmount($taxableAmount)
                ->setDiscountAmount($discountAmount)
                ->setRowTotal($rowTotal)
                ->setRowTax($rowTaxExact)
                ->setRowTotalInclTax($rowTotalInclTax);
        }

        $blockTax = $this->calculationTool->calcTaxAmount(
            $blockTaxableAmount,
            $taxRate,
            true,
            false
        );
        $blockTaxBeforeDiscount = $this->calculationTool->calcTaxAmount(
            $blockTaxableAmount + $blockDiscountAmount,
            $taxRate,
            true,
            false
        );
        $roundTax = $currencyRounding->round($currencyCode, $blockTax);

        $appliedTaxes = $this->getAppliedTaxes($roundTax, $taxRate, $appliedRates);
        $roundTaxBeforeDiscount = $currencyRounding->round($currencyCode, $blockTaxBeforeDiscount);
        $discountTaxCompensationAmount = $roundTaxBeforeDiscount - $roundTax;

        return $this->invoiceTaxBlockFactory->create()
            ->setTax($roundTax)
            ->setTotal($blockTotalInclTax - $roundTaxBeforeDiscount)
            ->setTotalInclTax($blockTotalInclTax)
            ->setTaxPercent($taxRate)
            ->setAppliedTaxes($appliedTaxes)
            ->setDiscountAmount($blockDiscountAmount)
            ->setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
            ->setItems($invoiceTaxItems)
            ->setIsTaxIncluded(true);
    }

    /**
     * Calculate Tax Not In Price
     *
     * @param array $items
     * @param int $taxRate
     * @param array $appliedRates
     * @param string $currencyCode
     * @return InvoiceTaxBlockInterface
     */
    public function calculateWithTaxNotInPrice(array $items, $taxRate, $appliedRates, $currencyCode)
    {
        $currencyRounding = $this->currencyRoundingFactory->create();
        $appliedTaxes = [];
        $blockTotal = 0;
        $blockTotalForTaxCalcAfterDiscount = 0;
        $blockDiscountAmount = 0;
        $invoiceTaxItems = [];
        $discountTaxCompensationAmount = 0;

        // Compute tax details for each item
        foreach ($items as $item) {
            $quantity = $item->getQuantity();
            $unitPrice = $item->getUnitPrice();
            $discountAmount = $item->getDiscountAmount();
            $rowTotal = $unitPrice * $quantity;

            $rowTaxBeforeDiscount = $this->calculationTool->calcTaxAmount($rowTotal, $taxRate, false, false);
            $rowTaxBeforeDiscount = $currencyRounding->round($currencyCode, $rowTaxBeforeDiscount);
            $rowTotalInclTax = $rowTotal + $rowTaxBeforeDiscount;
            $priceInclTax = $rowTotalInclTax / $quantity;

            $rowTotalForTaxCalcAfterDiscount = $rowTotal - $discountAmount;
            $rowTax = $this->calculationTool->calcTaxAmount($rowTotalForTaxCalcAfterDiscount, $taxRate, false, false);
            $rowTax = $currencyRounding->round($currencyCode, $rowTax);

            // Aggregate totals for all items.
            $blockDiscountAmount += $discountAmount;
            $blockTotalForTaxCalcAfterDiscount += $rowTotalForTaxCalcAfterDiscount;
            $blockTotal += $rowTotal;

            $invoiceTaxItems[] = $this->invoiceTaxItemFactory->create()
                ->setPrice($unitPrice)
                ->setPriceInclTax($priceInclTax)
                ->setCode($item->getCode())
                ->setType($item->getType())
                ->setTaxPercent($taxRate)
                ->setTaxableAmount($rowTotalForTaxCalcAfterDiscount)
                ->setDiscountAmount($discountAmount)
                ->setQuantity($quantity)
                ->setRowTotal($rowTotal)
                ->setRowTax($rowTax)
                ->setRowTotalInclTax($rowTotalInclTax);
        }

        $blockTaxes = [];
        $blockTaxesAfterDiscount = [];
        // The code might allow for multiple tax rates, but this is because it copies existing code.
        foreach ($appliedRates as $appliedRate) {
            $taxId = $appliedRate['id'];
            $taxRate = $appliedRate['percent'];
            $blockTaxPerRateAfterDiscount = $this->calculationTool->calcTaxAmount($blockTotalForTaxCalcAfterDiscount, $taxRate, false, false);
            $roundBlockTaxPerRateAfterDiscount = $currencyRounding->round($currencyCode, $blockTaxPerRateAfterDiscount);
            $blockTaxPerRate = $this->calculationTool->calcTaxAmount($blockTotal, $taxRate, false, false);
            $roundBlockTaxPerRate = $currencyRounding->round($currencyCode, $blockTaxPerRate);

            $appliedTaxes[$taxId] = $this->getAppliedTax(
                $roundBlockTaxPerRate,
                $appliedRate
            );
            $blockTaxesAfterDiscount[] = $roundBlockTaxPerRateAfterDiscount;
            $blockTaxes[] = $roundBlockTaxPerRate;
        }

        $blockTax = array_sum($blockTaxes);
        $blockTaxAfterDiscount = array_sum($blockTaxesAfterDiscount);
        $blockTotalInclTax = $blockTotal + $blockTax;

        return $this->invoiceTaxBlockFactory->create()
            ->setTax($blockTaxAfterDiscount)
            ->setTotal($blockTotal)
            ->setTotalInclTax($blockTotalInclTax)
            ->setTaxPercent($taxRate)
            ->setDiscountAmount($blockDiscountAmount)
            ->setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
            ->setAppliedTaxes($appliedTaxes)
            ->setItems($invoiceTaxItems)
            ->setIsTaxIncluded(false);
    }

    /**
     * Create AppliedTax data object based applied tax rates and tax amount
     *
     * @param float $blockTax
     * @param array $appliedRate
     * example:
     *  [
     *      'id' => 'id',
     *      'percent' => 7.5,
     *      'rates' => [
     *          'code' => 'code',
     *          'title' => 'title',
     *          'percent' => 5.3,
     *      ],
     *  ]
     * @return \Magento\Tax\Api\Data\AppliedTaxInterface
     */
    protected function getAppliedTax($blockTax, $appliedRate)
    {
        $appliedTaxDataObject = $this->appliedTaxDataObjectFactory->create();
        $appliedTaxDataObject->setAmount($blockTax);
        $appliedTaxDataObject->setPercent($appliedRate['percent']);
        $appliedTaxDataObject->setTaxRateKey($appliedRate['id']);

        /** @var  \Magento\Tax\Api\Data\AppliedTaxRateInterface[] $rateDataObjects */
        $rateDataObjects = [];
        foreach ($appliedRate['rates'] as $rate) {
            //Skipped position, priority and rule_id
            $rateDataObjects[$rate['code']] = $this->appliedTaxRateDataObjectFactory->create()
                ->setPercent($rate['percent'])
                ->setCode($rate['code'])
                ->setTitle($rate['title']);
        }
        $appliedTaxDataObject->setRates($rateDataObjects);
        return $appliedTaxDataObject;
    }

    /**
     * Create AppliedTax data object based on applied tax rates and tax amount
     *
     * @param float $blockTax
     * @param float $totalTaxRate
     * @param array $appliedRates May contain multiple tax rates when catalog price includes tax
     * example:
     *  [
     *      [
     *          'id' => 'id1',
     *          'percent' => 7.5,
     *          'rates' => [
     *              'code' => 'code1',
     *              'title' => 'title1',
     *              'percent' => 5.3,
     *          ],
     *      ],
     *      [
     *          'id' => 'id2',
     *          'percent' => 8.5,
     *          'rates' => [
     *              'code' => 'code2',
     *              'title' => 'title2',
     *              'percent' => 7.3,
     *          ],
     *      ],
     *  ]
     * @return \Magento\Tax\Api\Data\AppliedTaxInterface[]
     */
    protected function getAppliedTaxes($blockTax, $totalTaxRate, $appliedRates)
    {
        /** @var \Magento\Tax\Api\Data\AppliedTaxInterface[] $appliedTaxes */
        $appliedTaxes = [];
        $totalAppliedAmount = 0;
        foreach ($appliedRates as $appliedRate) {
            if ($appliedRate['percent'] == 0) {
                continue;
            }

            $appliedAmount = $blockTax / $totalTaxRate * $appliedRate['percent'];
            //Use delta rounding to split tax amounts for each tax rates between items
            $appliedAmount = $this->deltaRound(
                $appliedAmount,
                $appliedRate['id'],
                true,
                self::KEY_APPLIED_TAX_DELTA_ROUNDING
            );
            if ($totalAppliedAmount + $appliedAmount > $blockTax) {
                $appliedAmount = $blockTax - $totalAppliedAmount;
            }
            $totalAppliedAmount += $appliedAmount;

            $appliedTaxDataObject = $this->appliedTaxDataObjectFactory->create();
            $appliedTaxDataObject->setAmount($appliedAmount);
            $appliedTaxDataObject->setPercent($appliedRate['percent']);
            $appliedTaxDataObject->setTaxRateKey($appliedRate['id']);

            /** @var  \Magento\Tax\Api\Data\AppliedTaxRateInterface[] $rateDataObjects */
            $rateDataObjects = [];
            foreach ($appliedRate['rates'] as $rate) {
                //Skipped position, priority and rule_id
                $rateDataObjects[$rate['code']] = $this->appliedTaxRateDataObjectFactory->create()
                    ->setPercent($rate['percent'])
                    ->setCode($rate['code'])
                    ->setTitle($rate['title']);
            }
            $appliedTaxDataObject->setRates($rateDataObjects);
            $appliedTaxes[$appliedTaxDataObject->getTaxRateKey()] = $appliedTaxDataObject;
        }

        return $appliedTaxes;
    }

    /**
     * Round price based on previous rounding operation delta
     *
     * @param float $price
     * @param string $rate
     * @param bool $direction
     * @param string $type
     * @param bool $round
     * @return float
     */
    protected function deltaRound($price, $rate, $direction, $type = self::KEY_REGULAR_DELTA_ROUNDING, $round = true)
    {
        if ($price) {
            $rate = (string)$rate;
            $type = $type . $direction;
            // initialize the delta to a small number to avoid non-deterministic behavior with rounding of 0.5
            $delta = isset($this->roundingDeltas[$type][$rate]) ?
                $this->roundingDeltas[$type][$rate] :
                0.000001;
            $price += $delta;
            $roundPrice = $price;
            if ($round) {
                $roundPrice = $this->calculationTool->round($roundPrice);
            }
            $this->roundingDeltas[$type][$rate] = $price - $roundPrice;
            $price = $roundPrice;
        }
        return $price;
    }
}
