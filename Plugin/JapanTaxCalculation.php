<?php
namespace Japan\Tax\Plugin;

use Japan\Tax\Model\CurrencyRoundingFactory;
use Magento\Store\Model\StoreManagerInterface;
use \Magento\Tax\Model\TaxCalculation;
use \Magento\Tax\Api\Data\QuoteDetailsInterface;
use Psr\Log\LoggerInterface;

class JapanTaxCalculation
{
    /**
     * @var CurrencyRoundingFactory
     */
    private $currencyRoundingFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        CurrencyRoundingFactory $currencyRoundingFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
    ) {
        $this->currencyRoundingFactory = $currencyRoundingFactory;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
    }

    public function aroundCalculateTax(
        TaxCalculation $subject, 
        callable $proceed,
        QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true,
    ) {
        $baseCurrency = $this->_storeManager->getStore($storeId)->getBaseCurrencyCode();
        $currencyRounding = $this->currencyRoundingFactory->create();
        
        // Do whatever needed for Invoice tax calculation

        $taxDetails = $proceed($quoteDetails, $storeId, $round);

        // Usage examples
        $taxDetails->setSubtotal($currencyRounding->round($baseCurrency, $taxDetails->getSubtotal()));
        $taxDetails->setTaxAmount($currencyRounding->round($baseCurrency, $taxDetails->getTaxAmount()));

        return $taxDetails;
    }
}