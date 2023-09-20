<?php
namespace Japan\Tax\Observer;

use \Magento\Framework\DataObject\Copy;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class QuoteSubmitBeforeObserver implements ObserverInterface
{
    /**
     * @var Copy
     */
    protected $objectCopyService;

    public function __construct(Copy $objectCopyService)
    {
        $this->objectCopyService = $objectCopyService;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_quote_address',
            'to_order',
            $address,
            $order,
        );
    }
}
