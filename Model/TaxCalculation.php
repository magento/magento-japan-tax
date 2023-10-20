<?php

namespace Magentoj\JapaneseConsumptionTax\Model;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magentoj\JapaneseConsumptionTax\Api\TaxCalculationInterface;
use Magentoj\JapaneseConsumptionTax\Model\InvoiceTax\InvoiceTax;

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
     * @var \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterfaceFactory
     */
    protected $invoiceTaxFactory;

    /**
     * @var \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    /**
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Model\Calculation $calculation
     * @param \Magento\Tax\Api\TaxClassManagementInterface $taxClassManagement
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory
     * @param \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory
     * @param \Magento\Tax\Api\Data\InvoiceTaxInterfaceFactory $invoiceTaxFactory
     * @param \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator $jctTaxCalculator
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\Tax\Api\TaxClassManagementInterface $taxClassManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory,
        \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory,
        \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterfaceFactory $invoiceTaxFactory,
        \Magentoj\JapaneseConsumptionTax\Model\Calculation\JctTaxCalculator $jctTaxCalculator
    ) {
        $this->config = $taxConfig;
        $this->calculationTool = $calculation;
        $this->storeManager = $storeManager;
        $this->taxClassManagement = $taxClassManagement;
        $this->appliedTaxDataObjectFactory = $appliedTaxDataObjectFactory;
        $this->appliedTaxRateDataObjectFactory = $appliedTaxRateDataObjectFactory;
        $this->invoiceTaxFactory = $invoiceTaxFactory;
        $this->jctTaxCalculator = $jctTaxCalculator;
    }

    /**
     * Calculate Tax
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param bool $useBaseCurrency
     * @param null|int $storeId
     * @param bool $round
     * @return \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface
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

    /**
     * Calculate invoice based on quote details
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param int $storeId
     * @param bool $useBaseCurrency
     * @return \Magento\Tax\Model\InvoiceTax
     */
    protected function calculateInvoice(\Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails, $storeId, $useBaseCurrency)
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
            $storeRate = $this->getStoreRate(
                $quoteDetails->getShippingAddress(),
                $quoteDetails->getBillingAddress(),
                $this->taxClassManagement->getTaxClassId($quoteDetails->getCustomerTaxClassKey(), 'customer'),
                $storeId,
                $quoteDetails->getCustomerId(),
                $this->taxClassManagement->getTaxClassId($item->getTaxClassKey()),
            );
            $key = $rate;
            if (!isset($aggregate[$key])) {
                $aggregate[$key] = [
                    "appliedRates" => $appliedRates,
                    "taxRate" => $rate,
                    "storeRate" => $storeRate,
                    "items" => []
                ];
            }
            $aggregate[$key]["items"][] = $item;
            // Set isTaxIncluded flag to true if at least one item has tax included
            $isTaxIncluded = $isTaxIncluded || $item->getIsTaxIncluded();
        }

        $store = $this->storeManager->getStore($storeId);
        $currencyCode = $useBaseCurrency ? $store->getBaseCurrencyCode() : $store->getCurrentCurrencyCode();
        $blocks = [];

        if ($isTaxIncluded) {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $currencyCode
                );
            }
        } else {
            foreach ($aggregate as $data) {
                $blocks[] = $this->jctTaxCalculator->calculateWithTaxNotInPrice(
                    $data["items"],
                    $data["taxRate"],
                    $data["appliedRates"],
                    $currencyCode
                );
            }
        }

        return $this->invoiceTaxFactory->create()
            ->setBlocks($blocks);
    }

    /**
     * Retrieve the tax rate for the given parameters
     *
     * @param mixed $shippingAddress
     * @param mixed $billingAddress
     * @param int $customerTaxClassId
     * @param int $storeId
     * @param int $customerId
     * @param int $productTaxClassID
     * @return float
     */
    protected function getRate(
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

    /**
     * Retrieve the applied tax rates for the given parameters
     *
     * @param mixed $shippingAddress
     * @param mixed $billingAddress
     * @param int $customerTaxClassId
     * @param int $storeId
     * @param int $customerId
     * @param int $productTaxClassID
     * @return array
     */
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

    /**
     * Retrieve the store tax rate for the given parameters
     *
     * @param mixed $shippingAddress
     * @param mixed $billingAddress
     * @param int $customerTaxClassId
     * @param int $storeId
     * @param int $customerId
     * @param int $productTaxClassID
     * @return float
     */
    protected function getStoreRate(
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
        return $this->calculationTool->getStoreRate($addressRequestObject);
    }
}
