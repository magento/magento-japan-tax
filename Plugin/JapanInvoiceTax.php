<?php
namespace Japan\Tax\Plugin;

use Japan\Tax\Model\CurrencyRoundingFactory;
use \Magento\Tax\Model\Sales\Total\Quote\Tax;
use \Magento\Quote\Model\Quote;
use \Magento\Quote\Api\Data\ShippingAssignmentInterface;
use \Magento\Quote\Model\Quote\Address\Total;
use Psr\Log\LoggerInterface;

class JapanInvoiceTax
{
    /**
     * @var CurrencyRoundingFactory
     */
    private $currencyRoundingFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        CurrencyRoundingFactory $currencyRoundingFactory,
        LoggerInterface $logger,
    ) {
        $this->currencyRoundingFactory = $currencyRoundingFactory;
        $this->_logger = $logger;
    }

    public function aroundCollect(
        Tax $subject, 
        callable $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total,
    ) {
        // TODO: Do whatever needed for Invoice tax calculation
        $result = $proceed($quote, $shippingAssignment, $total);

        $baseCurrency = $quote->getBaseCurrencyCode();
        if ($baseCurrency === null) {
            $baseCurrency = $quote->getStore()->getBaseCurrencyCode();
        }
        $this->_logger->debug(sprintf('Base currency is %s', $baseCurrency));

        // Usage example of CurrencyRounding
        $currencyRounding = $this->currencyRoundingFactory->create();
        // $total->setBaseTaxAmount($this->round($baseCurrency, $total->getBaseTaxAmount()));

        return $result;
    }
}
