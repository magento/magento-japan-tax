<?php
namespace Japan\Tax\Plugin\Quote;

use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Psr\Log\LoggerInterface;

class Tax extends \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector
{
    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_config;

    /**
     * @var \Japan\Tax\Api\TaxCalculationInterface
     */
    private $japanTaxCalculationService;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory
     */
    protected $quoteDetailsDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory
     */
    protected $quoteDetailsItemDataObjectFactory;

    /**
     * @var QuoteDetailsItemExtensionInterfaceFactory
     */
    private $quoteDetailsItemExtensionFactory;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        \Japan\Tax\Api\TaxCalculationInterface $japanTaxCalculationService,
    ) {
        $this->japanTaxCalculationService = $japanTaxCalculationService;
        $this->quoteDetailsDataObjectFactory = $quoteDetailsDataObjectFactory;
        $this->_config = $taxConfig;
        $this->taxClassKeyDataObjectFactory = $taxClassKeyDataObjectFactory;
        $this->quoteDetailsItemDataObjectFactory = $quoteDetailsItemDataObjectFactory;
        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory,
        );
    }

    public function aroundCollect(
        \Magento\Tax\Model\Sales\Total\Quote\Tax $subject,
        callable $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total,
    ) {
        $baseInvoiceTax = $this->getQuoteInvoiceTax($subject, $shippingAssignment, $total, true);
        $invoiceTax = $this->getQuoteInvoiceTax($subject, $shippingAssignment, $total, false);
        $this->clearValues($total);
        $this->processInvoiceTax($shippingAssignment, $invoiceTax, $baseInvoiceTax, $total);

        return $subject;
    }

    public function afterFetch(
        \Magento\Tax\Model\Sales\Total\Quote\Tax $subject,
        array $result,
        Quote $quote,
        Total $total
    ) {
        array_push(
            $result,
            [
                'code' => 'subtotalExclJct10',
                'title' => __('Subtotal Subject to 10% Tax (Excl. Tax)'),
                'value' => $total->getSubtotalExclJct10(),
            ],
            [
                'code' => 'subtotalInclJct10',
                'title' => __('Subtotal Subject to 10% Tax (Incl. Tax)'),
                'value' => $total->getSubtotalInclJct10(),
            ],
            [
                'code' => 'subtotalExclJct8',
                'title' => __('Subtotal Subject to 8% Tax (Excl. Tax)'),
                'value' => $total->getSubtotalExclJct8(),
            ],
            [
                'code' => 'subtotalInclJct8',
                'title' => __('Subtotal Subject to 8% Tax (Incl. Tax)'),
                'value' => $total->getSubtotalInclJct8(),
            ],
            [
                'code' => 'jct10',
                'title' => __('10% Tax'),
                'value' => $total->getJct10Amount(),
            ],
            [
                'code' => 'jct8',
                'title' => __('8% Tax'),
                'value' => $total->getJct8Amount(),
            ]
        );

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

        $total->setSubtotalExclJct10(0);
        $total->setBaseSubtotalExclJct10(0);
        $total->setSubtotalInclJct10(0);
        $total->setBaseSubtotalInclJct10(0);
        $total->setJct10Amount(0);
        $total->setBaseJct10Amount(0);
        $total->setSubtotalExclJct8(0);
        $total->setBaseSubtotalExclJct8(0);
        $total->setSubtotalInclJct8(0);
        $total->setBaseSubtotalInclJct8(0);
        $total->setJct8Amount(0);
        $total->setBaseJct8Amount(0);
    }

    protected function getQuoteInvoiceTax(
        \Magento\Tax\Model\Sales\Total\Quote\Tax $tax, 
        $shippingAssignment, 
        $total, 
        $useBaseCurrency
    ) {
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
        $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, $itemDataObjects);

        return $this->japanTaxCalculationService
            ->calculateTax($quoteDetails, $useBaseCurrency, $address->getQuote()->getStore()->getStoreId());
    }

    protected function prepareQuoteDetails(ShippingAssignmentInterface $shippingAssignment, $itemDataObjects)
    {
        $items = $shippingAssignment->getItems();
        $address = $shippingAssignment->getShipping()->getAddress();
        if (empty($items)) {
            return $this->quoteDetailsDataObjectFactory->create();
        }

        $quoteDetails = $this->quoteDetailsDataObjectFactory->create();
        $this->populateAddressData($quoteDetails, $address);

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
        // TODO: update calculation for shipping and subtotal
        $subtotal = $baseSubtotal = 0;
        $discountTaxCompensation = $baseDiscountTaxCompensation = 0;
        $tax = $baseTax = 0;
        $subtotalInclTax = $baseSubtotalInclTax = 0;
        $shippingTotal = $baseShippingTotal = 0;
        $shippingTax = $baseShippingTax = 0;

        $keyedAddressItems = [];
        foreach ($shippingAssignment->getItems() as $addressItem) {
            $keyedAddressItems[$addressItem->getTaxCalculationItemId()] = $addressItem;
        }

        $appliedTaxes = [];
        foreach ($invoiceTax->getBlocks() as $block) {
            $subtotal += $block->getTotal();
            $discountTaxCompensation += $block->getDiscountTaxCompensationAmount();
            $tax += $block->getTax();
            $subtotalInclTax += $block->getTotalInclTax();

            foreach ($block->getItems() as $item) {
                if ($item->getType() == self::ITEM_TYPE_PRODUCT) {
                    $quoteItem = $keyedAddressItems[$item->getCode()];
                    $quoteItem->setTaxPercent($item->getTaxPercent());
                    $quoteItem->setPrice($item->getPrice());
                    $quoteItem->setPriceInclTax($item->getPriceInclTax());
                    $quoteItem->setRowTotal($item->getRowTotal());
                    $quoteItem->setRowTotalInclTax($item->getRowTotalInclTax());
                }
                if ($item->getType() == self::ITEM_TYPE_SHIPPING) {
                    $shippingTotal += $item->getRowTotal();
                    $shippingTax += $item->getRowTax();
                    $subtotal -= $item->getRowTotal();
                    $subtotalInclTax -= $item->getRowTotalInclTax();
                }
            }

            $taxPercent = (int) $block->getTaxPercent();
            if ($taxPercent === 10) {
                $total->setSubtotalExclJct10($block->getTotal());
                $total->setSubtotalInclJct10($block->getTotalInclTax());
                $total->setJct10Amount($block->getTax());
                $appliedTaxes += $block->getAppliedTaxes();
            } else if ($taxPercent === 8) {
                $total->setSubtotalExclJct8($block->getTotal());
                $total->setSubtotalInclJct8($block->getTotalInclTax());
                $total->setJct8Amount($block->getTax());
                $appliedTaxes += $block->getAppliedTaxes();
            }
        }

        $baseAppliedTaxes = [];
        foreach ($baseInvoiceTax->getBlocks() as $block) {
            $baseSubtotal += $block->getTotal();
            $baseDiscountTaxCompensation += $block->getDiscountTaxCompensationAmount();
            $baseTax += $block->getTax();
            $baseSubtotalInclTax += $block->getTotalInclTax();
            foreach ($block->getItems() as $item) {
                if ($item->getType() == self::ITEM_TYPE_PRODUCT) {
                    $quoteItem = $keyedAddressItems[$item->getCode()];
                    $quoteItem->setBaseTaxPercent($item->getTaxPercent());
                    $quoteItem->setBasePrice($item->getPrice());
                    $quoteItem->setBasePriceInclTax($item->getPriceInclTax());
                    $quoteItem->setBaseRowTotal($item->getRowTotal());
                    $quoteItem->setBaseRowTotalInclTax($item->getRowTotalInclTax());
                }
                if ($item->getType() == self::ITEM_TYPE_SHIPPING) {
                    $baseShippingTotal += $item->getRowTotal();
                    $baseShippingTax += $item->getRowTax();
                    $baseSubtotal -= $item->getRowTotal();
                    $baseSubtotalInclTax -= $item->getRowTotalInclTax();
                }
            }

            $taxPercent = (int) $block->getTaxPercent();
            if ($taxPercent === 10) {
                $total->setBaseSubtotalExclJct10($block->getTotal());
                $total->setBaseSubtotalInclJct10($block->getTotalInclTax());
                $total->setBaseJct10Amount($block->getTax());
                $baseAppliedTaxes += $block->getAppliedTaxes();
            } else if ($taxPercent === 8) {
                $total->setBaseSubtotalExclJct8($block->getTotal());
                $total->setBaseSubtotalInclJct8($block->getTotalInclTax());
                $total->setBaseJct8Amount($block->getTax());
                $baseAppliedTaxes += $block->getAppliedTaxes();
            }
        }

        $total->setAppliedTaxes([]);
        $appliedTaxesArray = $this->convertAppliedTaxes($appliedTaxes, $baseAppliedTaxes);
        foreach ($appliedTaxesArray as $appliedTaxArray) {
            $this->_saveAppliedTaxes(
                $total,
                [$appliedTaxArray],
                $appliedTaxArray['amount'],
                $appliedTaxArray['base_amount'],
                $appliedTaxArray['percent']
            );
        }

        // Set aggregated values
        $total->setTotalAmount('subtotal', $subtotal);
        $total->setBaseTotalAmount('subtotal', $baseSubtotal);
        $total->setTotalAmount('tax', $tax);
        $total->setBaseTotalAmount('tax', $baseTax);
        $total->setTotalAmount('shipping', $shippingTotal);
        $total->setBaseTotalAmount('shipping', $baseShippingTotal);
        $total->setTotalAmount('discount_tax_compensation', $discountTaxCompensation);
        $total->setBaseTotalAmount('discount_tax_compensation', $baseDiscountTaxCompensation);

        $total->setSubtotal($subtotal);
        $total->setBaseSubtotal($baseSubtotal);
        $total->setSubtotalInclTax($subtotalInclTax);
        $total->setBaseSubtotalTotalInclTax($baseSubtotalInclTax);
        $total->setBaseSubtotalInclTax($baseSubtotalInclTax);

        // shipping
        $total->setShippingAmount($shippingTotal);
        $total->setBaseShippingAmount($baseShippingTotal);
        $total->setShippingInclTax($shippingTotal + $shippingTax);
        $total->setBaseShippingInclTax($baseShippingTotal + $baseShippingTax);
        $total->setShippingTaxAmount($shippingTax);
        $total->setBaseShippingTaxAmount($baseShippingTax);

        $address = $shippingAssignment->getShipping()->getAddress();
        $address->setBaseTaxAmount($baseTax);
        $address->setBaseSubtotalTotalInclTax($baseSubtotalInclTax);
        $address->setSubtotalInclTax($subtotalInclTax);
        $address->setSubtotal($total->getSubtotal());
        $address->setBaseSubtotal($total->getBaseSubtotal());

        return $this;
    }
}
