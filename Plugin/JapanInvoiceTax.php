<?php
namespace Japan\Tax\Plugin;

use Magento\Framework\App\ObjectManager;
use \Magento\Tax\Model\Sales\Total\Quote\Tax;
use \Magento\Quote\Model\Quote;
use \Magento\Quote\Api\Data\ShippingAssignmentInterface;
use \Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Psr\Log\LoggerInterface;

class JapanInvoiceTax
{
    /**#@+
     * Constants defined for type of items
     */
    public const ITEM_TYPE_SHIPPING = 'shipping';
    public const ITEM_TYPE_PRODUCT = 'product';

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_config;

    /**
     * @var \Japan\Tax\Api\TaxCalculationInterface
     */
    private $taxCalculationService;


    /**
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory
     */
    protected $quoteDetailsItemDataObjectFactory;

    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * @var QuoteDetailsItemExtensionInterfaceFactory
     */
    private $quoteDetailsItemExtensionFactory;


    /**
     * @var CustomerAddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var CustomerAddressRegionFactory
     */
    protected $customerAddressRegionFactory;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Japan\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        TaxHelper $taxHelper = null,
    ) {
        $this->taxCalculationService = $taxCalculationService;
        $this->quoteDetailsDataObjectFactory = $quoteDetailsDataObjectFactory;
        $this->_config = $taxConfig;
        $this->taxClassKeyDataObjectFactory = $taxClassKeyDataObjectFactory;
        $this->quoteDetailsItemDataObjectFactory = $quoteDetailsItemDataObjectFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->customerAddressRegionFactory = $customerAddressRegionFactory;
        $this->taxHelper = $taxHelper ?: ObjectManager::getInstance()->get(TaxHelper::class);
    }

    public function aroundCollect(
        Tax $subject,
        callable $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total,
    ) {
        $this->clearValues($total);
        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        // $proceed($quote, $shippingAssignment, $total);
        $baseInvoiceTax = $this->getQuoteInvoiceTax($subject, $shippingAssignment, $total, true);
        $invoiceTax = $this->getQuoteInvoiceTax($subject, $shippingAssignment, $total, false);
        $this->processInvoiceTax($shippingAssignment, $invoiceTax, $baseInvoiceTax, $total);
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug("calculateTax: {$invoiceTax->toJson()}");
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug("calculateTax: {$total->toJson()}");

        $baseCurrency = $quote->getBaseCurrencyCode();
        if ($baseCurrency === null) {
            $baseCurrency = $quote->getStore()->getBaseCurrencyCode();
        }
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug(sprintf('Base currency is %s', $baseCurrency));

        // // Usage example of CurrencyRounding
        // $total->setBaseTaxAmount($this->currencyRoundingService->round($baseCurrency, $total->getBaseTaxAmount()));

        return $subject;
    }

    protected function clearValues(Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('shipping', 0);
        $total->setBaseTotalAmount('shipping', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
        $total->setShippingInclTax(0);
        $total->setBaseShippingInclTax(0);
        $total->setShippingTaxAmount(0);
        $total->setBaseShippingTaxAmount(0);
        $total->setShippingAmountForDiscount(0);
        $total->setBaseShippingAmountForDiscount(0);
        $total->setTotalAmount('extra_tax', 0);
        $total->setBaseTotalAmount('extra_tax', 0);
    }

    protected function getQuoteInvoiceTax(Tax $tax, $shippingAssignment, $total, $useBaseCurrency)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        //Setup taxable items
        $priceIncludesTax = $this->_config->priceIncludesTax($address->getQuote()->getStore());
        $itemDataObjects = $tax->mapItems($shippingAssignment, $priceIncludesTax, $useBaseCurrency);

        //Add shipping
        $shippingDataObject = $tax->getShippingDataObject($shippingAssignment, $total, $useBaseCurrency);
        if ($shippingDataObject != null) {
            $itemDataObjects[] = $shippingDataObject;
        }

        //process extra taxable items associated only with quote
        $quoteExtraTaxables = $tax->mapQuoteExtraTaxables(
            $this->quoteDetailsItemDataObjectFactory,
            $address,
            $useBaseCurrency
        );
        if (!empty($quoteExtraTaxables)) {
            $itemDataObjects = array_merge($itemDataObjects, $quoteExtraTaxables);
        }

        //Preparation for calling taxCalculationService
        $quoteDetails = $this->prepareQuoteDetails($tax, $shippingAssignment, $itemDataObjects);

        return $this->taxCalculationService
            ->calculateTax($quoteDetails, $address->getQuote()->getStore()->getStoreId());
    }

    protected function prepareQuoteDetails(Tax $tax, ShippingAssignmentInterface $shippingAssignment, $itemDataObjects)
    {
        $items = $shippingAssignment->getItems();
        $address = $shippingAssignment->getShipping()->getAddress();
        if (empty($items)) {
            return $this->quoteDetailsDataObjectFactory->create();
        }

        $quoteDetails = $this->quoteDetailsDataObjectFactory->create();
        $tax->populateAddressData($quoteDetails, $address);

        //Set customer tax class
        $quoteDetails->setCustomerTaxClassKey(
            $this->taxClassKeyDataObjectFactory->create()
                ->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue($address->getQuote()->getCustomerTaxClassId())
        );
        $quoteDetails->setItems($itemDataObjects);
        $quoteDetails->setCustomerId($address->getQuote()->getCustomerId());

        return $quoteDetails;
    }

    protected function processInvoiceTax(
        ShippingAssignmentInterface $shippingAssignment,
        \Japan\Tax\Api\Data\InvoiceTaxInterface $invoiceTax,
        \Japan\Tax\Api\Data\InvoiceTaxInterface $baseInvoiceTax,
        Total $total
    ) {
        $subtotal = $baseSubtotal = 0;
        $discountTaxCompensation = $baseDiscountTaxCompensation = 0;
        $tax = $baseTax = 0;
        $subtotalInclTax = $baseSubtotalInclTax = 0;
        $shippingUnitPrice = $baseShippingUnitPrice = 0;

        foreach ($invoiceTax->getBlocks() as $block) {
            $subtotal += $block->getTotal();
            $discountTaxCompensation += $block->getDiscountTaxCompensationAmount();
            $tax += $block->getTax();
            $subtotalInclTax += $block->getTotalInclTax();
            foreach ($block->getItems() as $item) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get('Psr\Log\LoggerInterface')
                    ->debug("block item: {$item->toJson()}");
                if ($item->getType() == self::ITEM_TYPE_SHIPPING) {
                    $shippingUnitPrice += $item->getPrice();
                    // $subtotal -= $item->getPrice();
                }
            }
        }

        foreach ($baseInvoiceTax->getBlocks() as $block) {
            $baseSubtotal += $block->getTotal();
            $baseDiscountTaxCompensation += $block->getDiscountTaxCompensationAmount();
            $baseTax += $block->getTax();
            $baseSubtotalInclTax += $block->getTotalInclTax();
            foreach ($block->getItems() as $item) {
                if ($item->getType() == self::ITEM_TYPE_SHIPPING) {
                    $baseShippingUnitPrice += $item->getPrice();
                    // $baseSubtotal -= $item->getPrice();
                }
            }
        }

        //Set aggregated values
        $total->setTotalAmount('subtotal', $subtotal);
        $total->setBaseTotalAmount('subtotal', $baseSubtotal);
        $total->setTotalAmount('tax', $tax);
        $total->setBaseTotalAmount('tax', $baseTax);
        $total->setTotalAmount('discount_tax_compensation', $discountTaxCompensation);
        $total->setBaseTotalAmount('discount_tax_compensation', $baseDiscountTaxCompensation);

        $total->setShippingInclTax($shippingUnitPrice);
        $total->setBaseShippingInclTax($baseShippingUnitPrice);
        $total->setShippingAmount($shippingUnitPrice);
        $total->setBaseShippingAmount($baseShippingUnitPrice);
        $total->setShippingTaxAmount(0);
        $total->setBaseShippingTaxAmount(0);
        $total->setShippingAmountForDiscount(0);
        $total->setBaseShippingAmountForDiscount(0);

        $total->setSubtotalInclTax($subtotalInclTax);
        $total->setBaseSubtotalTotalInclTax($baseSubtotalInclTax);
        $total->setBaseSubtotalInclTax($baseSubtotalInclTax);
        $address = $shippingAssignment->getShipping()->getAddress();
        $address->setBaseTaxAmount($baseTax);
        $address->setBaseSubtotalTotalInclTax($baseSubtotalInclTax);
        $address->setSubtotalInclTax($subtotalInclTax);
        $address->setSubtotal($total->getSubtotal());
        $address->setBaseSubtotal($total->getBaseSubtotal());

        return $this;
    }
}
