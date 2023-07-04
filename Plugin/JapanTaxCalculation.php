<?php
namespace Japan\Tax\Plugin;

use Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use Magento\Tax\Model\TaxCalculation;

class JapanTaxCalculation
{
    /**
     * Tax Details factory
     *
     * @var TaxDetailsInterfaceFactory
     */
    private $taxDetailsDataObjectFactory;

    public function __construct(
        TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory,
    ) {
        $this->taxDetailsDataObjectFactory = $taxDetailsDataObjectFactory;
    }

    public function aroundCalculateTax(TaxCalculation $subject, callable $proceed,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true,
    ) {
        $items = $quoteDetails->getItems();

        $keyedItems = [];
        $parentToChildren = [];
        foreach ($items as $item) {
            if ($item->getParentCode() === null) {
                $keyedItems[$item->getCode()] = $item;
            } else {
                $parentToChildren[$item->getParentCode()][] = $item;
            }
        }
        
        $processedItems = [];
        /** @var QuoteDetailsItemInterface $item */
        foreach ($keyedItems as $item) {
            if (isset($parentToChildren[$item->getCode()])) {
                $processedChildren = [];
                foreach ($parentToChildren[$item->getCode()] as $child) {
                    // $processedItem = $this->processItem($child, $calculator, $round);
                    $processedItem = $child;
                    // $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
                    $processedItems[$processedItem->getCode()] = $processedItem;
                    $processedChildren[] = $processedItem;
                }
                // $processedItem = $this->calculateParent($processedChildren, $item->getQuantity());
                $processedItem->setCode($item->getCode());
                $processedItem->setType($item->getType());
            } else {
                // $processedItem = $this->processItem($item, $calculator, $round);
                $processedItem = $item;
                // $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
            }
            $processedItems[$processedItem->getCode()] = $processedItem;
        }

        return $this->taxDetailsDataObjectFactory->create()
            ->setSubtotal(0.0)
            ->setTaxAmount(0.0)
            ->setDiscountTaxCompensationAmount(0.0)
            ->setAppliedTaxes([])
            ->setItems($keyedItems);

        // return $$proceed($quoteDetails, $storeId, $round);
    }
}