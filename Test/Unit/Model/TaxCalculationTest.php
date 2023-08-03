<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Japan\Tax\Test\Unit\Model;

use Japan\Tax\Model\TaxCalculation;
use Japan\Tax\Api\Data\InvoiceTaxInterfaceFactory;
use Japan\Tax\Api\Data\InvoiceTaxBlockInterfaceFactory;
use Japan\Tax\Api\Data\InvoiceTaxItemInterfaceFactory;
use Japan\Tax\Model\InvoiceTax\InvoiceTax;
use Japan\Tax\Model\InvoiceTax\InvoiceTaxBlock;
use Japan\Tax\Model\InvoiceTax\InvoiceTaxItem;
use Japan\Tax\Model\CurrencyRoundingFactory;
use Japan\Tax\Model\CurrencyRounding;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\TaxDetails\AppliedTax;
use Magento\Tax\Model\TaxDetails\AppliedTaxRate;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\AppliedTaxInterfaceFactory;
use Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Shipping;
use Magento\Quote\Model\Quote\ShippingAssignment;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Quote\TotalsCollectorFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class TaxCalculationTest extends TestCase
{
    const TAX_CLASS_ID_10 = '10';
    const TAX_CLASS_ID_8  = '8';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var TaxClassManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxClassManagementMock;

    /**
     * @var AppliedTaxInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $appliedTaxDataObjectFactoryMock;

    /**
     * @var AppliedTaxRateInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $appliedTaxRateDataObjectFactoryMock;

    /**
     * @var InvoiceTaxInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $invoiceTaxFactoryMock;

    /**
     * @var InvoiceTaxBlockInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $invoiceTaxBlockFactoryMock;

    /**
     * @var InvoiceTaxItemInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $invoiceTaxItemFactoryMock;

    /**
     * @var CurrencyRoundingFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyRoundingFactoryMock;

    /**
     * @var Calculation|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $calculationMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxConfigMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxClassManagementMock = $this->getMockBuilder(TaxClassManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Use the mockFactory method
        $this->appliedTaxDataObjectFactoryMock = $this->mockFactory(
            AppliedTaxInterfaceFactory::class,
            AppliedTax::class
        );

        $this->appliedTaxRateDataObjectFactoryMock = $this->mockFactory(
            AppliedTaxRateInterfaceFactory::class,
            AppliedTaxRate::class
        );

        $this->invoiceTaxFactoryMock = $this->mockFactory(
            InvoiceTaxInterfaceFactory::class,
            InvoiceTax::class
        );

        $this->invoiceTaxBlockFactoryMock = $this->mockFactory(
            InvoiceTaxBlockInterfaceFactory::class,
            InvoiceTaxBlock::class
        );

        $this->invoiceTaxItemFactoryMock = $this->mockFactory(
            InvoiceTaxItemInterfaceFactory::class,
            InvoiceTaxItem::class
        );

        $this->calculationMock = $this->mockCreateCalculationTool();

        $this->taxConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyRoundingFactoryMock = $this->mockFactory(
            CurrencyRoundingFactory::class,
            CurrencyRounding::class
        );

        $this->taxCalculation = $this->objectManager->getObject(
            TaxCalculation::class,
            [
                'storeManager' => $this->storeManagerMock,
                'taxClassManagement' => $this->taxClassManagementMock,
                'appliedTaxDataObjectFactory' => $this->appliedTaxDataObjectFactoryMock,
                'appliedTaxRateDataObjectFactory' => $this->appliedTaxRateDataObjectFactoryMock,
                'invoiceTaxFactory' => $this->invoiceTaxFactoryMock,
                'invoiceTaxBlockFactory' => $this->invoiceTaxBlockFactoryMock,
                'invoiceTaxItemFactory' => $this->invoiceTaxItemFactoryMock,
                'currencyRoundingFactory' => $this->currencyRoundingFactoryMock,
                'calculation' => $this->calculationMock,
                'taxConfig' => $this->taxConfigMock,
            ]
        );
    }

    /**
     * Test for calculateTax method with 10 percent tax
     */
    public function testCalculateTax10Percent()
    {
        $storeId = 1;
        $baseCurrency = 'JPY';
        $round = true;

        $this->storeManagerMock
            ->method('getStore')
            ->willReturnSelf();

        $quoteDetailsMock = $this->getMockForAbstractClass(QuoteDetailsInterface::class);
        $quoteDetailsMock->method('getItems')
            ->willReturn($this->mockCreateQuoteDetailsItems([
                [
                    'code' => 'item1',
                    'type' => 'product',
                    'tax_class_key' => 'key1',
                    'unit_price' => 100,
                    'quantity' => 1,
                    'is_tax_included' => false,
                    'short_description' => 'Item 1',
                    'discount_amount' => 0.0,
                    'tax_class_id' => self::TAX_CLASS_ID_10
                ],
                [
                    'code' => 'item2',
                    'type' => 'product',
                    'tax_class_key' => 'key2',
                    'unit_price' => 100,
                    'quantity' => 2,
                    'is_tax_included' => false,
                    'short_description' => 'Item 2',
                    'discount_amount' => 0.0,
                    'tax_class_id' => self::TAX_CLASS_ID_8
                ]
            ]));

        $res = $this->taxCalculation->calculateTax($quoteDetailsMock, $baseCurrency, $storeId, $round);
        foreach ($res->getBlocks() as $block) {
            $this->assertEquals(10, $block->getTaxPercent());
            $this->assertEquals(300, $block->getTotal());
            $this->assertEquals(330, $block->getTotalInclTax());
            $this->assertEquals(30, $block->getTax());
        }
    }

    private function mockFactory(string $factoryName, string $instanceType)
    {
        $mock = $this->createPartialMock($factoryName, ['create']);
        $mock
            ->method('create')
            ->willReturnCallback(fn() => $this->objectManager->getObject($instanceType));

        return $mock;
    }

    private function mockCreateQuoteDetailsItems(array $data = [])
    {
        $quoteDetailsItems = [];
        foreach ($data as $itemData) {
            $item = $this->getMockForAbstractClass(QuoteDetailsItemInterface::class);

            $item->method('getCode')->willReturn($itemData['code']);
            $item->method('getType')->willReturn($itemData['type']);
            $item->method('getTaxClassKey')->willReturn($itemData['tax_class_key']);
            $item->method('getUnitPrice')->willReturn($itemData['unit_price']);
            $item->method('getQuantity')->willReturn($itemData['quantity']);
            $item->method('getIsTaxIncluded')->willReturn($itemData['is_tax_included']);
            $item->method('getShortDescription')->willReturn($itemData['short_description']);
            $item->method('getDiscountAmount')->willReturn($itemData['discount_amount']);
            $item->method('getTaxClassId')->willReturn($itemData['tax_class_id']);

            $quoteDetailsItems[] = $item;
        }

        return $quoteDetailsItems;
    }

    private function mockCreateCalculationTool()
    {
        $calculationToolMock = $this->getMockBuilder(Calculation::class)
            ->disableOriginalConstructor()
            ->getMock();

        // FIXME: fix calculationToolMock->calcTaxAmount
        $calculationToolMock
            ->method('calcTaxAmount')
            ->willReturnCallback(function ($amount, $taxRate, $isIncludingTax, $round = true) {
                return $amount * $taxRate / 100;
            });

        $calculationToolMock
            ->method('getRateRequest')
            ->willReturnCallback(function ($shippingAddress, $billingAddress, $customerTaxClassId, $storeId, $customerId) {
                $addressRequestObjectMock = $this->getMockBuilder(DataObject::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['getCustomerTaxClassId'])
                    ->getMock();
                $addressRequestObjectMock
                    ->method('getCustomerTaxClassId')
                    ->willReturn($customerTaxClassId);
                return $addressRequestObjectMock;
            });

        // TODO: dont use hardcoded values
        $calculationToolMock
            ->method('getRate')
            ->willReturnCallback(function ($addressRequestObject) {
                if (self::TAX_CLASS_ID_8 == $addressRequestObject->getCustomerTaxClassId() )
                    return 8;
                return 10;
            });

        // TODO: dont use hardcoded values
        $calculationToolMock
            ->method('getAppliedRates')
            ->willReturnCallback(function ($addressRequestObject) {
                return [[
                    'id' => 1,
                    'percent' => 10,
                    'rates' => [[
                        'code' => 'test_10',
                        'percent' => 10,
                        'title' => 'test_10'
                    ]]
                ]];
            });

        return $calculationToolMock;
    }
}
