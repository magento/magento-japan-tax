<?php
namespace Japan\Tax\Block\Sales\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        array $data = [],
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    public function initTotals()
    {
        $taxInclude = (int) $this->scopeConfig->getValue(
            'tax/calculation/price_includes_tax',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'subtotal_subject_to_jct10',
                'value' => $taxInclude ?
                    $this->getOrder()->getSubtotalInclJct10() : $this->getOrder()->getSubtotalExclJct10(),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 10% Tax (Incl. Tax)') : __('Subtotal Subject to 10% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'subtotal');

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'subtotal_subject_to_jct8',
                'value' => $taxInclude ?
                    $this->getOrder()->getSubtotalInclJct8() : $this->getOrder()->getSubtotalExclJct8(),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 8% Tax (Incl. Tax)') : __('Subtotal Subject to 8% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'subtotal');

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'jct10_amount',
                'value' => $this->getOrder()->getJct10Amount(),
                'label' => __('10% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'tax');

        $taxDataObject = new \Magento\Framework\DataObject(
            [
                'code' => 'jct8_amount',
                'value' => $this->getOrder()->getJct8Amount(),
                'label' => __('8% Tax'),
            ]
        );
        $this->getParentBlock()->addTotalBefore($taxDataObject, 'tax');

        $this->getParentBlock()->removeTotal('subtotal');
        $this->getParentBlock()->removeTotal('tax');

        return $this;
    }
}
