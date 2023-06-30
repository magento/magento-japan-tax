<?php
namespace Japan\Tax\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

echo("CatalogProductViewObserver");

class CatalogProductViewObserver implements ObserverInterface
{
    
    public function __construct(
    ) {
        echo("CatalogProductViewObserver construct");
    }

    public function execute(Observer $observer)
    {
        echo("CatalogProductViewObserver execute");
    }
}
