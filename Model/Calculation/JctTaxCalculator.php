<?php

namespace Japan\Tax\Model\Calculation;

use Japan\Tax\Model\CurrencyRoundingFactory;

/**
 * aggregate calculator.
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
     * @var \Japan\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory
     */
    protected $invoiceTaxBlockFactory;

    /**
     * @var \Japan\Tax\Api\Data\InvoiceTaxItemInterfaceFactory
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
     * @param \Magento\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory
     * @param \Magento\Tax\Api\Data\InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory
     * @param CurrencyRoundingFactory $currencyRoundingFactory
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory,
        \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory,
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory,
        \Japan\Tax\Api\Data\InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory,
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
     * @param int $storeRate
     * @param int $storeId
     * @param array $appliedRates
     * @param string $currencyCode
     * @return \Japan\Tax\Api\Data\InvoiceTaxBlockInterface
     */
    public function calculateWithTaxInPrice(array $items, $taxRate, $storeRate, $storeId, $appliedRates, $currencyCode)
    {
        $currencyRounding = $this->currencyRoundingFactory->create();
        $appliedTaxes = [];
        $blockTax = 0;
        $blockTotalInclTax = 0;
        $invoiceTaxItems = [];
        $blockDiscountAmount = 0;
        $blockTaxableAmount = 0;

        foreach($items as $item) {
            $quantity = $item->getQuantity();
            $discountAmount = $item->getDiscountAmount();
            $priceInclTax = $item->getUnitPrice();
            $rowTotalInclTax = $priceInclTax * $quantity;
            if (!$this->isSameRateAsStore($taxRate, $storeRate, $storeId)) {
                $priceInclTax = $this->calculatePriceInclTax($priceInclTax, $storeRate, $taxRate, $currencyCode);
                $totalInclTax = $priceInclTax * $quantity;
            }
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
     * @param int $storeRate
     * @param array $appliedRates
     * @param string $currencyCode
     * @return \Japan\Tax\Api\Data\InvoiceTaxBlockInterface
     */
    public function calculateWithTaxNotInPrice(array $items, $taxRate, $storeRate, $appliedRates, $currencyCode)
    {
        $currencyRounding = $this->currencyRoundingFactory->create();
        $appliedTaxes = [];
        $blockTotal = 0;
        $blockTotalForTaxCalcAfterDiscount = 0;
        $blockDiscountAmount = 0;
        $invoiceTaxItems = [];
        $discountTaxCompensationAmount = 0;

        foreach($items as $item) {
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
        //Apply each tax rate separately
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
     * Check if tax rate is same as store tax rate
     *
     * @param float $rate
     * @param float $storeRate
     * @return bool
     */
    protected function isSameRateAsStore($rate, $storeRate, $storeId)
    {
        if ((bool)$this->config->crossBorderTradeEnabled($storeId)) {
            return true;
        } else {
            return (abs($rate - $storeRate) < 0.00001);
        }
    }

    /**
     * Given a store price that includes tax at the store rate, this function will back out the store's tax, and add in
     * the customer's tax.  Returns this new price which is the customer's price including tax.
     *
     * @param float $storePriceInclTax
     * @param float $storeRate
     * @param float $customerRate
     * @param string $currencyCode
     * @return float
     */
    protected function calculatePriceInclTax($storePriceInclTax, $storeRate, $customerRate, $currencyCode)
    {
        $currencyRounding = $this->currencyRoundingFactory->create();
        $storeTax = $this->calculationTool->calcTaxAmount($storePriceInclTax, $storeRate, true, false);
        $storeTax = $currencyRounding->round($currencyCode, $storeTax);
        $priceExclTax = $storePriceInclTax - $storeTax;
        $customerTax = $this->calculationTool->calcTaxAmount($priceExclTax, $customerRate, false, false);
        $customerTax = $currencyRounding->round($currencyCode, $customerTax);
        return $priceExclTax + $customerTax;
    }

    protected function getAppliedTax($tax, $appliedRate)
    {
        $appliedTaxDataObject = $this->appliedTaxDataObjectFactory->create();
        $appliedTaxDataObject->setAmount($tax);
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

    protected function getAppliedTaxes($rowTax, $totalTaxRate, $appliedRates)
    {
        /** @var \Magento\Tax\Api\Data\AppliedTaxInterface[] $appliedTaxes */
        $appliedTaxes = [];
        $totalAppliedAmount = 0;
        foreach ($appliedRates as $appliedRate) {
            if ($appliedRate['percent'] == 0) {
                continue;
            }

            $appliedAmount = $rowTax / $totalTaxRate * $appliedRate['percent'];
            //Use delta rounding to split tax amounts for each tax rates between items
            $appliedAmount = $this->deltaRound(
                $appliedAmount,
                $appliedRate['id'],
                true,
                self::KEY_APPLIED_TAX_DELTA_ROUNDING
            );
            if ($totalAppliedAmount + $appliedAmount > $rowTax) {
                $appliedAmount = $rowTax - $totalAppliedAmount;
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
