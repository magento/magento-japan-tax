<?php
namespace Japan\Tax\Plugin;

use \Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use \Magento\Tax\Model\TaxCalculation;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class JapanTaxCalculation
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Tax Details factory
     *
     * @var TaxDetailsInterfaceFactory
     */
    private $taxDetailsDataObjectFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory,
        LoggerInterface $logger,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->taxDetailsDataObjectFactory = $taxDetailsDataObjectFactory;
        $this->_logger = $logger;
    }

    /**
     * Return configured rounding mode for currencies.
     *
     * @return string
     */
    public function getRoundingMode(): string
    {
        $configuredValue = $this->scopeConfig->getValue(
            'currency/options/rounding_mode',
            ScopeInterface::SCOPE_WEBSITE
        );
        return $configuredValue;
    }

    public function aroundCalculateTax(TaxCalculation $subject, callable $proceed,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true,
    ) {
        $this->_logger->debug("Rounding Mode: {$this->getRoundingMode()}");
        
        // Do whatever needed for Invoice tax calculation

        return $proceed($quoteDetails, $storeId, $round);
    }
}