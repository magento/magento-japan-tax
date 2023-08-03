<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Japan\Tax\Test\Unit\Plugin;

use Japan\Tax\Plugin\JapanInvoiceTax;
use Japan\Tax\Api\TaxCalculationInterface;
use Japan\Tax\Api\Data\InvoiceTaxInterface;
use Japan\Tax\Api\Data\InvoiceTaxBlockInterface;
use Japan\Tax\Api\Data\InvoiceTaxItemInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;
use Magento\Tax\Model\TaxClass\Key;
use Magento\Tax\Model\Sales\Quote\QuoteDetails;
use Magento\Store\Model\Store;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\Sales\Quote\ItemDetails;
use Magento\Tax\Model\Sales\Total\Quote\Tax;
use Magento\Tax\Helper\Data as TaxHelper;

class JapanInvoiceTaxTest extends TestCase
{
    private $objectManager;
    private $quoteMock;
    private $shippingAssignmentMock;
    private $totalMock;
    private $taxMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->quoteMock = $this->mockQuote();
        $this->shippingAssignmentMock = $this->mockShippingAssignment();
        $this->totalMock = $this->createMock(Total::class);
        $this->taxMock = $this->mockTax();
    }

    /**
     * Test method for the case where there are no items to be taxed
     */
    public function testAroundCollectWithNoItems()
    {
        $japanInvoiceTax = $this->objectManager->getObject(
            JapanInvoiceTax::class,
            [
                'taxConfig' => $this->mockTaxConfig(),
                'taxCalculationService' => $this->mockTaxCalculationService([]),
                'quoteDetailsDataObjectFactory' => $this->mockFactory(
                    QuoteDetailsInterfaceFactory::class, QuoteDetails::class)
                ,
                'quoteDetailsItemDataObjectFactory' => $this->mockFactory(
                    QuoteDetailsItemInterfaceFactory::class, ItemDetails::class
                ),
                'taxClassKeyDataObjectFactory' => $this->mockFactory(
                    TaxClassKeyInterfaceFactory::class, Key::class
                ),
                'taxHelper' => $this->taxHelperMock(),
            ]
        );

        $total = $this->objectManager->getObject(
            Total::class,
        );

        $result = $japanInvoiceTax->aroundCollect(
            $this->taxMock,
            function () {},
            $this->quoteMock,
            $this->shippingAssignmentMock,
            $total
        );

        $this->assertEquals($total->getTotalAmount('subtotal'), 0);
        $this->assertEquals($total->getBaseTotalAmount('subtotal'), 0);
        $this->assertEquals($total->getTotalAmount('tax'), 0);
        $this->assertEquals($total->getBaseTotalAmount('tax'), 0);
        $this->assertEquals($total->getShippingAmount(), 0);
        $this->assertEquals($total->getBaseShippingAmount(), 0);
    }

    /**
     * Test method for the case where there are items to be taxed
     */
    public function testAroundCollectWithItems()
    {
        $total = $this->objectManager->getObject(
            Total::class,
        );

        $invoiceTaxData = [
            [
                'total' => 1000,
                'total_incl_tax' => 1100,
                'tax' => 100,
                'tax_percent' => 10,
                'items' => [
                    [
                        'price' => 200,
                        'code' => 'sequence-1',
                        'type' => 'product',
                        'quantity' => 1,
                        'row_total' => 200,
                    ],
                    [
                        'price' => 400,
                        'code' => 'sequence-2',
                        'type' => 'product',
                        'quantity' => 2,
                        'row_total' => 800,
                    ]
                ],
            ],
            [
                'total' => 500,
                'total_incl_tax' => 540,
                'tax' => 40,
                'tax_percent' => 8,
                'items' => [
                    [
                        'price' => 500,
                        'code' => 'sequence-3',
                        'type' => 'product',
                        'quantity' => 1,
                        'row_total' => 500,
                    ]
                ],
            ]
        ];

        $japanInvoiceTax = $this->objectManager->getObject(
            JapanInvoiceTax::class,
            [
                'taxConfig' => $this->mockTaxConfig(),
                'taxCalculationService' => $this->mockTaxCalculationService($invoiceTaxData),
                'quoteDetailsDataObjectFactory' => $this->mockFactory(
                    QuoteDetailsInterfaceFactory::class, QuoteDetails::class)
                ,
                'quoteDetailsItemDataObjectFactory' => $this->mockFactory(
                    QuoteDetailsItemInterfaceFactory::class, ItemDetails::class
                ),
                'taxClassKeyDataObjectFactory' => $this->mockFactory(
                    TaxClassKeyInterfaceFactory::class, Key::class
                ),
                'taxHelper' => $this->taxHelperMock(),
            ]
        );

        $result = $japanInvoiceTax->aroundCollect(
            $this->taxMock,
            function () {},
            $this->quoteMock,
            $this->shippingAssignmentMock,
            $total
        );

        $this->assertEquals($total->getTotalAmount('subtotal'), 1500);
        $this->assertEquals($total->getBaseTotalAmount('subtotal'), 1500);
        $this->assertEquals($total->getTotalAmount('tax'), 140);
        $this->assertEquals($total->getBaseTotalAmount('tax'), 140);
        $this->assertEquals($total->getShippingAmount(), 0);
        $this->assertEquals($total->getBaseShippingAmount(), 0);
    }

    /**
     * Test method for the case where there are items and shipping to be taxed
     */
    public function testAroundCollectWithItemsAndShipping()
    {
        $total = $this->objectManager->getObject(
            Total::class,
        );

        $invoiceTaxData = [
            [
                'total' => 210,
                'total_incl_tax' => 231,
                'tax' => 21,
                'tax_percent' => 10,
                'items' => [
                    [
                        'price' => 200,
                        'code' => 'sequence-1',
                        'type' => 'product',
                        'quantity' => 1,
                        'row_total' => 200,
                    ],
                    [
                        'price' => 10,
                        'code' => 'sequence-2',
                        'type' => 'shipping',
                        'quantity' => 1,
                        'row_total' => 10,
                    ]
                ],
            ],
            [
                'total' => 500,
                'total_incl_tax' => 540,
                'tax' => 40,
                'tax_percent' => 8,
                'items' => [
                    [
                        'price' => 500,
                        'code' => 'sequence-3',
                        'type' => 'product',
                        'quantity' => 1,
                        'row_total' => 500,
                    ]
                ],
            ]
        ];

        $japanInvoiceTax = $this->objectManager->getObject(
            JapanInvoiceTax::class,
            [
                'taxConfig' => $this->mockTaxConfig(),
                'taxCalculationService' => $this->mockTaxCalculationService($invoiceTaxData),
                'quoteDetailsDataObjectFactory' => $this->mockFactory(
                    QuoteDetailsInterfaceFactory::class, QuoteDetails::class)
                ,
                'quoteDetailsItemDataObjectFactory' => $this->mockFactory(
                    QuoteDetailsItemInterfaceFactory::class, ItemDetails::class
                ),
                'taxClassKeyDataObjectFactory' => $this->mockFactory(
                    TaxClassKeyInterfaceFactory::class, Key::class
                ),
                'taxHelper' => $this->taxHelperMock(),
            ]
        );

        $result = $japanInvoiceTax->aroundCollect(
            $this->taxMock,
            function () {},
            $this->quoteMock,
            $this->shippingAssignmentMock,
            $total
        );

        $this->assertEquals($total->getTotalAmount('subtotal'), 710.);
        $this->assertEquals($total->getBaseTotalAmount('subtotal'), 710.);
        $this->assertEquals($total->getTotalAmount('tax'), 61);
        $this->assertEquals($total->getBaseTotalAmount('tax'), 61);
        $this->assertEquals($total->getShippingAmount(), 10);
        $this->assertEquals($total->getBaseShippingAmount(), 10);
    }

    protected function mockQuote()
    {
        $extensionAttributes = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setInvoiceTax'])
            ->getMock();
        $extensionAttributes
            ->method('setInvoiceTax');
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getBaseCurrencyCode'])
            ->onlyMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->method('getBaseCurrencyCode')
            ->willReturn('JPY');
        $quoteMock->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        return $quoteMock;
    }

    protected function mockTaxConfig()
    {
        $taxConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $taxConfig;
    }

    protected function mockTaxCalculationService(array $data = [])
    {
        $blocks = [];
        foreach ($data as $blockData) {
            $block = $this->getMockForAbstractClass(InvoiceTaxBlockInterface::class);
            $block->method('getTotal')->willReturn($blockData['total']);
            $block->method('getTotalInclTax')->willReturn($blockData['total_incl_tax']);
            $block->method('getTax')->willReturn($blockData['tax']);
            $block->method('setTaxPercent')->willReturn($blockData['tax_percent']);
            $items = [];
            foreach ($blockData['items'] as $itemData) {
                $item = $this->getMockForAbstractClass(InvoiceTaxItemInterface::class);
                $item->method('getPrice')->willReturn($itemData['price']);
                $item->method('getCode')->willReturn($itemData['code']);
                $item->method('getType')->willReturn($itemData['type']);
                $item->method('getQuantity')->willReturn($itemData['quantity']);
                $item->method('getRowTotal')->willReturn($itemData['row_total']);
                $items[] = $item;
            }
            $block->method('getItems')->willReturn($items);
            $blocks[] = $block;
        }

        $invoiceTax = $this->getMockForAbstractClass(InvoiceTaxInterface::class);
        $invoiceTax->method('getBlocks')
            ->willReturn($blocks);

        $taxCalculationServiceMock = $this->createPartialMock(
            TaxCalculationInterface::class,
            ['calculateTax']
        );
        $taxCalculationServiceMock->method('calculateTax')
            ->willReturn($invoiceTax);

        return $taxCalculationServiceMock;
    }

    protected function taxHelperMock()
    {
        return $this->createMock(TaxHelper::class);
    }

    protected function mockShippingAssignment()
    {
        $storeMock = $this->getMockBuilder(Store::class)
            ->addMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getStoreId')
            ->willReturn(1);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getStore')
            ->willReturn($storeMock);

        $addressMock = $this->createMock(Address::class);
        $addressMock->method('getQuote')
            ->willReturn($quoteMock);

        $shippingObjectMock = $this->getMockForAbstractClass(ShippingInterface::class);
        $shippingObjectMock->method('getAddress')
            ->willReturn($addressMock);

        $shippingAssignmentMock = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->setMethods(['toJson', 'getShipping'])
            ->getMockForAbstractClass();

        $shippingAssignmentMock->method('getShipping')
            ->willReturn($shippingObjectMock);

        return $shippingAssignmentMock;
    }

    private function mockTax()
    {
        $taxMock = $this->createMock(Tax::class);

        $itemDataObjectMock = $this->getMockBuilder(QuoteDetailsItemInterface::class)
            ->setMethods(['getAssociatedTaxables'])
            ->getMockForAbstractClass();
        $taxMock->method('mapItems')->willReturn([
            $itemDataObjectMock
        ]);

        $shippingItemDataObjectMock = $this->getMockBuilder(QuoteDetailsItemInterface::class)
            ->setMethods(['getAssociatedTaxables'])
            ->getMockForAbstractClass();
        $taxMock->method('getShippingDataObject')->willReturn([
            $shippingItemDataObjectMock
        ]);

        $taxMock->method('mapQuoteExtraTaxables')->willReturn([]);
        $taxMock->method('populateAddressData');

        return $taxMock;
    }

    private function mockFactory(string $factoryName, string $instanceType)
    {
        $mock = $this->createPartialMock($factoryName, ['create']);
        $mock
            ->method('create')
            ->willReturnCallback(fn() => $this->objectManager->getObject($instanceType));

        return $mock;
    }
}
