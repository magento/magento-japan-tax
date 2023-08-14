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
     * @var array
     */
    private $roundingDeltas = [];

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
     * @param bool $useBaseCurrency
     * @param null|int $storeId
     * @param bool $round
     * @return \Japan\Tax\Api\Data\InvoiceTaxInterface
     */
    public function calculateTax(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $useBaseCurrency,
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

        return $this->calculateInvoice($quoteDetails, $storeId, $useBaseCurrency);
    }

    protected function calculateInvoice(\Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails, $storeId, $useBaseCurrency, $round = true)
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
            $res = $this->calculateWithTaxInPrice($aggregate, $storeId, null, $useBaseCurrency, $round);
        } else {
            $res = $this->calculateWithTaxNotInPrice($aggregate, $storeId, $useBaseCurrency, $round);
        }

        return $this->invoiceTaxFactory->create()
            ->setBlocks($res);
    }

    protected function calculateWithTaxInPrice($aggregate, $storeId, $storeRate, $useBaseCurrency, $round = true)
    {
        // TODO: update calucation logic
        $currencyRounding = $this->currencyRoundingFactory->create();
        $res = [];
        foreach ($aggregate as $code => $data) {
            // Calculate $rowTotal
            $appliedTaxes = [];
            $blockTax = 0;
            $blockTotalInclTax = 0;
            $rate = $data["taxRate"];
            $invoiceTaxItems = [];
            $blockDiscountAmount = 0;

            foreach($data["items"] as $item) {
                $quantity = $item->getQuantity();
                $discountAmount = $item->getDiscountAmount();
                $priceInclTax = $item->getUnitPrice();
                $totalInclTax = $priceInclTax * $quantity;
                $taxableAmount = max($totalInclTax - $discountAmount, 0);
                $tax = $this->calculationTool->calcTaxAmount(
                    $taxableAmount,
                    $rate,
                    true,
                    false
                );
                $blockDiscountAmount += $discountAmount;

                $blockTax += $tax;
                $blockTotalInclTax += $totalInclTax;

                $invoiceTaxItems[] = $this->invoiceTaxItemFactory->create()
                    ->setPrice($priceInclTax)
                    ->setCode($item->getCode())
                    ->setType($item->getType())
                    ->setTaxPercent($rate)
                    ->setQuantity($quantity)
                    ->setDiscountAmount($discountAmount)
                    ->setRowTotal($priceInclTax * $quantity);
            }

            $appliedTaxes = $this->getAppliedTaxes($tax, $rate, $data["appliedRates"]);

            $store = $this->storeManager->getStore($storeId);
            $currencyCode = $useBaseCurrency ? $store->getBaseCurrencyCode() : $store->getStoreCurrencyCode();

            $roundTax = $currencyRounding->round($currencyCode, $blockTax);
            $roundBlockTotalInclTax = $currencyRounding->round($currencyCode, $blockTotalInclTax);
            $res[] = $this->invoiceTaxBlockFactory->create()
                ->setTax($roundTax)
                ->setTotal($roundBlockTotalInclTax - $roundTax)
                ->setTotalInclTax($roundBlockTotalInclTax)
                ->setTaxPercent($rate)
                ->setAppliedTaxes($appliedTaxes)
                ->setDiscountAmount($blockDiscountAmount)
                ->setItems($invoiceTaxItems);
        }

        return $res;
    }

    protected function calculateWithTaxNotInPrice($aggregate, $storeId, $useBaseCurrency, $round = true)
    {
        // TODO: update calucation logic
        $currencyRounding = $this->currencyRoundingFactory->create();
        $res = [];
        foreach ($aggregate as $code => $data) {
            // Calculate $rowTotal
            $appliedTaxes = [];
            $blockTotal = 0;
            $blockTotalForTaxCalculation = 0;
            $blockDiscountAmount = 0;
            $rate = $data["taxRate"];
            $invoiceTaxItems = [];

            foreach($data["items"] as $item) {
                $quantity = $item->getQuantity();
                $unitPrice = $item->getUnitPrice();
                $discountAmount = $item->getDiscountAmount();
                $unitPriceForTaxCalc = $this->getPriceForTaxCalculation($item, $unitPrice);

                $blockDiscountAmount += $discountAmount;
                $blockTotalForTaxCalculation += $unitPriceForTaxCalc * $quantity - $discountAmount;
                $blockTotal += $unitPrice * $quantity;
                $invoiceTaxItems[] = $this->invoiceTaxItemFactory->create()
                    ->setPrice($unitPrice)
                    ->setCode($item->getCode())
                    ->setType($item->getType())
                    ->setTaxPercent($rate)
                    ->setDiscountAmount($discountAmount)
                    ->setQuantity($quantity)
                    ->setRowTotal($unitPrice * $quantity);
            }

            $blockTaxes = [];
            //Apply each tax rate separately
            foreach ($data["appliedRates"] as $appliedRate) {
                $taxId = $appliedRate['id'];
                $taxRate = $appliedRate['percent'];
                $blockTaxPerRate = $this->calculationTool->calcTaxAmount($blockTotalForTaxCalculation, $taxRate, false, false);

                $appliedTaxes[$taxId] = $this->getAppliedTax(
                    $blockTaxPerRate,
                    $appliedRate
                );
                $blockTaxes[] = $blockTaxPerRate;
            }

            $blockTax = array_sum($blockTaxes);
            $blockTotalInclTax = $blockTotal + $blockTax;

            $store = $this->storeManager->getStore($storeId);
            $currencyCode = $useBaseCurrency ? $store->getBaseCurrencyCode() : $store->getStoreCurrencyCode();

            $res[] = $this->invoiceTaxBlockFactory->create()
                ->setTax($currencyRounding->round($currencyCode, $blockTax))
                ->setTotal($currencyRounding->round($currencyCode, $blockTotal))
                ->setTotalInclTax($currencyRounding->round($currencyCode, $blockTotalInclTax))
                ->setTaxPercent($rate)
                ->setDiscountAmount($blockDiscountAmount)
                ->setAppliedTaxes($appliedTaxes)
                ->setItems($invoiceTaxItems);

            // \Magento\Framework\App\ObjectManager::getInstance()
            //     ->get('Psr\Log\LoggerInterface')
            //     ->debug("invoiceTaxBlock: {$res[count($res) - 1]->toJson()}");
        }
        return $res;
    }

    function getRate(
        $shippingAddress,
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
            $shippingAddress,
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
        $shippingAddress,
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
            $shippingAddress,
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
