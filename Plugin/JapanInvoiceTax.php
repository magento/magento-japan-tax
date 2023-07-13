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
    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_config;

    /**
     * @var \Japan\Tax\Api\CurrencyRoundingInterface
     */
    private $currencyRoundingService;

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
        \Japan\Tax\Api\CurrencyRoundingInterface $currencyRoundingService,
        \Japan\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        TaxHelper $taxHelper = null,
    ) {
        $this->currencyRoundingService = $currencyRoundingService;
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

        $baseTaxDetails = $this->getQuoteTaxDetails($subject, $shippingAssignment, $total, true);
        $taxDetails = $this->getQuoteTaxDetails($subject, $shippingAssignment, $total, false);

        // TODO: Do whatever needed for Invoice tax calculation
        $result = $proceed($quote, $shippingAssignment, $total);

        // $res = $this->taxCalculationService->calculateTax($quote, $shippingAssignment, $total);

        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug("calculateTax: {$taxDetails}");

        $baseCurrency = $quote->getBaseCurrencyCode();
        if ($baseCurrency === null) {
            $baseCurrency = $quote->getStore()->getBaseCurrencyCode();
        }
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug(sprintf('Base currency is %s', $baseCurrency));

        // Usage example of CurrencyRounding
        $total->setBaseTaxAmount($this->currencyRoundingService->round($baseCurrency, $total->getBaseTaxAmount()));

        return $result;
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

    protected function getQuoteTaxDetails(Tax $tax, $shippingAssignment, $total, $useBaseCurrency)
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

        $taxDetails = $this->taxCalculationService
            ->calculateTax($quoteDetails, $address->getQuote()->getStore()->getStoreId());

        return $taxDetails;
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
}
