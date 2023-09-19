<?php
namespace Japan\Tax\Plugin\Creditmemo;

class Tax
{
    /**
     * @var \Japan\Tax\Model\Calculation\JctTaxCalculator
     */
    private $jctTaxCalculator;

    public function __construct(
        \Japan\Tax\Model\Calculation\JctTaxCalculator $jctTaxCalculator,
    ) {
        $this->jctTaxCalculator = $jctTaxCalculator;
    }

    public function afterCollect(
        \Magento\Sales\Model\Order\Creditmemo\Total\Tax $subject,
        \Magento\Sales\Model\Order\Creditmemo\Total\Tax $result,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
    ) {
        $order = $creditmemo->getOrder();

        return $result;
    }
}
