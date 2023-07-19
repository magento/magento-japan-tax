<?php

namespace Japan\Tax\Model;

use Japan\Tax\Model\CurrencyRoundingFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Japan\Tax\Api\TaxCalculationInterface;
use Japan\Tax\Model\InvoiceTax\InvoiceTax;

class TaxCalculation implements TaxCalculationInterface
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
     * @var \Magento\Tax\Model\Calculatio
     */
    protected $calculationTool;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Tax Class Management
     *
     * @var \Magento\Tax\Api\TaxClassManagementInterface
     */
    protected $taxClassManagement;


    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory
     */
    protected $appliedTaxDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory
     */
    protected $appliedTaxRateDataObjectFactory;

    /**
     * @var \Japan\Tax\Api\Data\InvoiceTaxInterfaceFactory
     */
    protected $invoiceTaxFactory;

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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Tax\Api\Data\InvoiceTaxInterfaceFactory $invoiceTaxFactory
     * @param \Magento\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory
     * @param \Magento\Tax\Api\Data\InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\Tax\Api\TaxClassManagementInterface $taxClassManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory,
        \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory,
        \Japan\Tax\Api\Data\InvoiceTaxInterfaceFactory $invoiceTaxFactory,
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory,
        \Japan\Tax\Api\Data\InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory,
        CurrencyRoundingFactory $currencyRoundingFactory,
    ) {
        $this->config = $taxConfig;
        $this->calculationTool = $calculation;
        $this->storeManager = $storeManager;
        $this->taxClassManagement = $taxClassManagement;
        $this->appliedTaxDataObjectFactory = $appliedTaxDataObjectFactory;
        $this->appliedTaxRateDataObjectFactory = $appliedTaxRateDataObjectFactory;
        $this->invoiceTaxFactory = $invoiceTaxFactory;
        $this->invoiceTaxBlockFactory = $invoiceTaxBlockFactory;
        $this->invoiceTaxItemFactory = $invoiceTaxItemFactory;
        $this->currencyRoundingFactory = $currencyRoundingFactory;
    }

     /**
     * Calculate Tax
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param null|string $baseCurrency
     * @param null|int $storeId
     * @param bool $round
     * @return \Japan\Tax\Api\Data\InvoiceTaxInterface
     */
    public function calculateTax(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $baseCurrency = null,
        $storeId = null,
        $round = true
    ) {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }

        // initial TaxDetails data
        $taxDetailsData = [
            InvoiceTax::KEY_SUBTOTAL => 0.0,
            InvoiceTax::KEY_TAX_AMOUNT => 0.0,
            // InvoiceTax::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT => 0.0,
            InvoiceTax::KEY_APPLIED_TAXES => [],
            InvoiceTax::KEY_BLOCKS => [],
        ];
        $items = $quoteDetails->getItems();
        if (empty($items)) {
            return $this->invoiceTaxFactory->create()
                ->setSubtotal(0.0)
                ->setTaxAmount(0.0)
                ->setDiscountTaxCompensationAmount(0.0)
                ->setAppliedTaxes([])
                ->setBlocks([]);
        }

        return $this->calculateInvoice($quoteDetails, $storeId, $baseCurrency);
    }

    protected function calculateInvoice(\Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails, $storeId, $baseCurrency, $round = true)
    {
        $invoiceTax = $this->invoiceTaxFactory->create();
        $items = $quoteDetails->getItems();
        $aggregate = [];
        $isTaxIncluded = false;

        foreach ($items as $item) {
            $rate = $this->getRate(
                $quoteDetails->getShippingAddress(),
                $quoteDetails->getBillingAddress(),
                $this->taxClassManagement->getTaxClassId($quoteDetails->getCustomerTaxClassKey(), 'customer'),
                $storeId,
                $quoteDetails->getCustomerId(),
                $this->taxClassManagement->getTaxClassId($item->getTaxClassKey()),
            );
            $appliedRates = $this->getAppliedRates(
                $quoteDetails->getShippingAddress(),
                $quoteDetails->getBillingAddress(),
                $this->taxClassManagement->getTaxClassId($quoteDetails->getCustomerTaxClassKey(), 'customer'),
                $storeId,
                $quoteDetails->getCustomerId(),
                $this->taxClassManagement->getTaxClassId($item->getTaxClassKey()),
            );
            $key = $rate;
            if (!isset($aggregate[$key])) {
                $aggregate[$key] = array(
                    "appliedRates" => $appliedRates,
                    "taxRate" => $rate,
                    "items" => []
                );
            }
            $aggregate[$key]["items"][] = $item;
            $isTaxIncluded = $isTaxIncluded || $item->getIsTaxIncluded();
        }

        if ($isTaxIncluded) {
            $res = $this->calculateWithTaxInPrice($aggregate, $storeId, null, $baseCurrency, $round);
        } else {
            $res = $this->calculateWithTaxNotInPrice($aggregate, $storeId, $baseCurrency, $round);
        }

        return $this->invoiceTaxFactory->create()
            ->setBlocks($res);
    }

    protected function calculateWithTaxInPrice($aggregate, $storeId, $storeRate, $baseCurrency, $round = true)
    {
        // TODO: update calucation logic
        $currencyRounding = $this->currencyRoundingFactory->create();
        $res = [];
        foreach ($aggregate as $code => $data) {
            // Calculate $rowTotal
            $appliedTaxes = [];
            $total = 0;
            $tax = 0;
            $totalInclTax = 0;
            $rate = $data["taxRate"];
            $invoiceTaxItems = [];

            foreach($data["items"] as $item) {
                $quantity = $item->getQuantity();
                $curPriceInclTax = $item->getUnitPrice();
                $curTotalInclTax = $curPriceInclTax * $quantity;
                $curTax = $this->calculationTool->calcTaxAmount($curTotalInclTax, $rate, true, false);

                $total += $curPriceInclTax - $curTax;
                $tax += $curTax;
                $totalInclTax += $curTotalInclTax;

                $invoiceTaxItems[] = $this->invoiceTaxItemFactory->create()
                    ->setPrice($curPriceInclTax)
                    ->setCode($item->getCode())
                    ->setType($item->getType())
                    ->setQuantity($quantity)
                    ->setRowTotal($curPriceInclTax * $quantity);
            }

            $appliedTaxes = $this->getAppliedTaxes($tax, $rate, $data["appliedRates"]);

            $res[] = $this->invoiceTaxBlockFactory->create()
                ->setTax($currencyRounding->round($baseCurrency, $tax))
                ->setTotal($currencyRounding->round($baseCurrency, $total))
                ->setTotalInclTax($currencyRounding->round($baseCurrency, $totalInclTax))
                ->setTaxPercent($rate)
                ->setAppliedTaxes($appliedTaxes)
                ->setItems($invoiceTaxItems);
        }

        return $res;
    }

    protected function calculateWithTaxNotInPrice($aggregate, $storeId, $baseCurrency, $round = true)
    {
        // TODO: update calucation logic
        $currencyRounding = $this->currencyRoundingFactory->create();
        $res = [];
        foreach ($aggregate as $code => $data) {
            // Calculate $rowTotal
            $appliedTaxes = [];
            $total = 0;
            $totalForTaxCalculation = 0;
            $rate = $data["taxRate"];
            $invoiceTaxItems = [];

            foreach($data["items"] as $item) {
                $quantity = $item->getQuantity();
                $unitPrice = $item->getUnitPrice();
                $totalForTaxCalculation += $this->getPriceForTaxCalculation($item, $unitPrice) * $quantity;
                $total += $unitPrice * $quantity;
                $invoiceTaxItems[] = $this->invoiceTaxItemFactory->create()
                    ->setPrice($unitPrice)
                    ->setCode($item->getCode())
                    ->setType($item->getType())
                    ->setQuantity($quantity)
                    ->setRowTotal($unitPrice * $quantity);
            }

            $taxes = [];
            //Apply each tax rate separately
            foreach ($data["appliedRates"] as $appliedRate) {
                $taxId = $appliedRate['id'];
                $taxRate = $appliedRate['percent'];
                $taxPerRate = $this->calculationTool->calcTaxAmount($totalForTaxCalculation, $taxRate, false, false);

                $appliedTaxes[$taxId] = $this->getAppliedTax(
                    $taxPerRate,
                    $appliedRate
                );
                $taxes[] = $taxPerRate;
            }

            $tax = array_sum($taxes);
            $totalInclTax = $total + $tax;

            $res[] = $this->invoiceTaxBlockFactory->create()
                ->setTax($currencyRounding->round($baseCurrency, $$tax))
                ->setTotal($currencyRounding->round($baseCurrency, $$total))
                ->setTotalInclTax($currencyRounding->round($baseCurrency, $$totalInclTax))
                ->setTaxPercent($rate)
                ->setAppliedTaxes($appliedTaxes)
                ->setItems($invoiceTaxItems);

            // \Magento\Framework\App\ObjectManager::getInstance()
            //     ->get('Psr\Log\LoggerInterface')
            //     ->debug("invoiceTaxBlock: {$res[count($res) - 1]->toJson()}");
        }
        return $res;
    }

    function getRate(
        $shippingAddres,
        $billingAddress,
        $customerTaxClassId,
        $storeId,
        $customerId,
        $productTaxClassID
    ) {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }
        $addressRequestObject = $this->calculationTool->getRateRequest(
            $shippingAddres,
            $billingAddress,
            $customerTaxClassId,
            $storeId,
            $customerId
        );
        $addressRequestObject->setProductClassId($productTaxClassID);
        return $this->calculationTool->getRate($addressRequestObject);
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

    protected function getAppliedRates(
        $shippingAddres,
        $billingAddress,
        $customerTaxClassId,
        $storeId,
        $customerId,
        $productTaxClassID
    ) {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }
        $addressRequestObject = $this->calculationTool->getRateRequest(
            $shippingAddres,
            $billingAddress,
            $customerTaxClassId,
            $storeId,
            $customerId
        );
        $addressRequestObject->setProductClassId($productTaxClassID);
        return $this->calculationTool->getAppliedRates($addressRequestObject);
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

    private function getPriceForTaxCalculation(QuoteDetailsItemInterface $item, float $price)
    {
        if ($item->getExtensionAttributes() && $item->getExtensionAttributes()->getPriceForTaxCalculation()) {
            $priceForTaxCalculation = $this->calculationTool->round(
                $item->getExtensionAttributes()->getPriceForTaxCalculation()
            );
        } else {
            $priceForTaxCalculation = $price;
        }

        return $priceForTaxCalculation;
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

    protected function roundAmount(
        $amount,
        $rate = null,
        $direction = null,
        $type = self::KEY_REGULAR_DELTA_ROUNDING,
        $round = true,
        $item = null
    ) {
        return $this->deltaRound($amount, $rate, $direction, $type, $round);
    }
}