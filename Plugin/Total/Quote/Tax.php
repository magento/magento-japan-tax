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
namespace Magentoj\JapaneseConsumptionTax\Plugin\Total\Quote;

use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magentoj\JapaneseConsumptionTax\Api\TaxCalculationInterface;
use Magentoj\JapaneseConsumptionTax\Api\Data\JctTotalsInterfaceFactory;
use Magentoj\JapaneseConsumptionTax\Constants;
use Psr\Log\LoggerInterface;

class Tax extends \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector
{
    use \Magentoj\JapaneseConsumptionTax\Plugin\Total\JctTotalsSetupTrait;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory
     */
    protected $quoteDetailsDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory
     */
    protected $quoteDetailsItemDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyDataObjectFactory;

    /**
     * @var QuoteDetailsItemExtensionInterfaceFactory
     */
    private $quoteDetailsItemExtensionFactory;

    /**
     * @var TaxCalculationInterface
     */
    private $japanTaxCalculationService;

    /**
     * @var JctTotalsInterfaceFactory;
     */
    private $jctTotalsInterfaceFactory;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        TaxCalculationInterface $japanTaxCalculationService,
        JctTotalsInterfaceFactory $jctTotalsInterfaceFactory
    ) {
        $this->_config = $taxConfig;
        $this->quoteDetailsDataObjectFactory = $quoteDetailsDataObjectFactory;
        $this->quoteDetailsItemDataObjectFactory = $quoteDetailsItemDataObjectFactory;
        $this->taxClassKeyDataObjectFactory = $taxClassKeyDataObjectFactory;
        $this->japanTaxCalculationService = $japanTaxCalculationService;
        $this->jctTotalsInterfaceFactory = $jctTotalsInterfaceFactory;
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

    /**
     * Calculate the tax details.
     *
     * @param \Magento\Tax\Model\Sales\Total\Quote\Tax $tax
     * @param mixed $shippingAssignment
     * @param mixed $total
     * @param bool $useBaseCurrency
     *
     * @return \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface
     */
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

    /**
     * Processes tax details and sets calculated data into the total.
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface $invoiceTax
     * @param \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface $baseInvoiceTax
     * @param Total $total
     *
     * @return $this
     */
    protected function processInvoiceTax(
        ShippingAssignmentInterface $shippingAssignment,
        \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface $invoiceTax,
        \Magentoj\JapaneseConsumptionTax\Api\Data\InvoiceTaxInterface $baseInvoiceTax,
        Total $total
    ) {
        $subtotal = $baseSubtotal = 0;
        $discountTaxCompensation = $baseDiscountTaxCompensation = 0;
        $tax = $baseTax = 0;
        $subtotalInclTax = $baseSubtotalInclTax = 0;
        $shippingTotal = $baseShippingTotal = 0;
        $shippingTax = $baseShippingTax = 0;
        $jctTotals = [];

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
                    $quoteItem->setPrice($item->getPrice());
                    $quoteItem->setPriceInclTax($item->getPriceInclTax());
                    $quoteItem->setRowTotal($item->getRowTotal());
                    $quoteItem->setRowTotalInclTax($item->getRowTotalInclTax());
                    $quoteItem->setTaxPercent($item->getTaxPercent());
                    $quoteItem->setTaxAmount($item->getRowTax());
                }
                if ($item->getType() == self::ITEM_TYPE_SHIPPING) {
                    $shippingTotal += $item->getRowTotal();
                    $shippingTax += $item->getRowTax();
                    $subtotal -= $item->getRowTotal();
                    $subtotalInclTax -= $item->getRowTotalInclTax();
                }
            }

            if (in_array($block->getTaxPercent(), Constants::JCT_PERCENTS)) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block, 'base_');
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
                    $quoteItem->setBasePrice($item->getPrice());
                    $quoteItem->setBasePriceInclTax($item->getPriceInclTax());
                    $quoteItem->setBaseRowTotal($item->getRowTotal());
                    $quoteItem->setBaseRowTotalInclTax($item->getRowTotalInclTax());
                    $quoteItem->setBaseTaxPercent($item->getTaxPercent());
                    $quoteItem->setBaseTaxAmount($item->getRowTax());
                }
                if ($item->getType() == self::ITEM_TYPE_SHIPPING) {
                    $baseShippingTotal += $item->getRowTotal();
                    $baseShippingTax += $item->getRowTax();
                    $baseSubtotal -= $item->getRowTotal();
                    $baseSubtotalInclTax -= $item->getRowTotalInclTax();
                }
            }

            if (in_array($block->getTaxPercent(), Constants::JCT_PERCENTS)) {
                $jctTotals = $this->updateJctTotalsArray($jctTotals, $block);
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

        $total->setJctTotals(
            $this->jctTotalsInterfaceFactory->create(
                [
                    'data' => $jctTotals
                ]
            )
        );

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
