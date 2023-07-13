<?php

namespace Japan\Tax\Model;

use Japan\Tax\Api\TaxCalculationInterface;

class TaxCalculation implements TaxCalculationInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Tax\Api\Data\InvoiceTaxInterfaceFactory $invoiceTaxFactory
     * @param \Magento\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory
     * @param \Magento\Tax\Api\Data\InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory
     * @param TaxCalculationInterface $taxCalculationService
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Japan\Tax\Api\Data\InvoiceTaxInterfaceFactory $invoiceTaxFactory,
        \Japan\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory $invoiceTaxBlockFactory,
        \Japan\Tax\Api\Data\InvoiceTaxItemInterfaceFactory $invoiceTaxItemFactory,
    ) {
        $this->_storeManager = $storeManager;
        $this->invoiceTaxFactory = $invoiceTaxFactory;
        $this->invoiceTaxBlockFactory = $invoiceTaxBlockFactory;
        $this->invoiceTaxItemFactory = $invoiceTaxItemFactory;
    }

     /**
     * Calculate Tax
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param null|int $storeId
     * @param bool $round
     * @return \Japan\Tax\Api\Data\InvoiceTaxInterface
     */
    public function calculateTax(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true
    ) {
        return 111;
    }
}