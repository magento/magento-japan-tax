<?php

namespace Magentoj\JapaneseConsumptionTax\Plugin\Total\Quote;

use Magentoj\JapaneseConsumptionTax\Model\CurrencyRoundingFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Psr\Log\LoggerInterface;

class Subtotal
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
        \Magento\Tax\Model\Sales\Total\Quote\Subtotal $subject,
        callable $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total,
    ) {
        $this->_logger->debug(sprintf('Skipping the default Subtotal collector'));

        return $subject;
    }
}
