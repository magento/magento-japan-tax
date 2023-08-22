<?php
namespace Japan\Tax\Model\Sales\Pdf;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

class Subtotal extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
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
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function getTotalsForDisplay()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $taxInclude = (int) $this->scopeConfig->getValue(
            'tax/calculation/price_includes_tax',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        $subtotalInfo = [
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $taxInclude ? 
                        $this->getOrder()->getSubtotalInclJct10() : $this->getOrder()->getSubtotalExclJct10()
                ),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 10% Tax (Incl. Tax)') : __('Subtotal Subject to 10% Tax'),
                'font_size' => $fontSize,
            ],
            [
                'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt(
                    $taxInclude ?
                        $this->getOrder()->getSubtotalInclJct8() : $this->getOrder()->getSubtotalExclJct8()
                ),
                'label' => $taxInclude ?
                    __('Subtotal Subject to 8% Tax (Incl. Tax)') : __('Subtotal Subject to 8% Tax'),
                'font_size' => $fontSize,
            ]
        ];

        return $subtotalInfo;
    }
}
