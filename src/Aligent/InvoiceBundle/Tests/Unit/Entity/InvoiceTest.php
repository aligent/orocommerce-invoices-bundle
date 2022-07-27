<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Tests\Unit\Entity;

use Aligent\InvoiceBundle\Entity\Invoice as BaseInvoice;
use Aligent\InvoiceBundle\Entity\InvoiceLineItem;
use Aligent\InvoiceBundle\Tests\Unit\Entity\Stub\Invoice;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class InvoiceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
            ['invoiceNo', 'invoice-test-01', 'INV001'],
            ['customer', new Customer()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['issueDate', $now, false],
            ['dueDate', $now, false],
            ['amount', 123.45, 10, 99999999.99],
            ['totalTax', 0.0, 123.45, 10, 99999999.99],
            ['amountPaid', 0.0, 123.45, 10, 99999999.99],
        ];

        $this->assertPropertyAccessors(new Invoice(), $properties);
        $this->assertPropertyCollection(new Invoice(), 'lineItems', new InvoiceLineItem());
    }

    public function testSetInvoiceStatus(): void
    {
        $invoiceStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $invoiceStatus->expects($this->any())
            ->method('getId')
            ->willReturn(BaseInvoice::STATUS_OPEN);

        /** @var Invoice $invoice */
        $invoice = $this->getEntity(Invoice::class, ['id' => 1]);

        $this->assertPropertyGetterReturnsDefaultValue($invoice, 'status');

        $invoice->setStatus($invoiceStatus);

        $this->assertPropertyGetterReturnsSetValue($invoice, 'status', $invoiceStatus);
        $this->assertEquals(BaseInvoice::STATUS_OPEN, $invoice->getStatus()->getId());
    }

    public function testInvoiceStatuses(): void
    {
        $statuses = Invoice::getStatuses();
        $this->assertIsArray($statuses);
        $this->assertNotEmpty($statuses);
        $this->assertArrayHasKey(BaseInvoice::STATUS_OPEN, $statuses);
        $this->assertArrayHasKey(BaseInvoice::STATUS_CANCELLED, $statuses);
        $this->assertArrayHasKey(BaseInvoice::STATUS_PAID, $statuses);
        $this->assertArrayHasKey(BaseInvoice::STATUS_OVERDUE, $statuses);
    }

    public function testInvoicePrices(): void
    {
        $this->assertPropertyGetterReturnsDefaultValue(new Invoice(), 'price');

        /** @var Invoice $invoice */
        $invoice = $this->getEntity(Invoice::class, [
            'amount' => 123.45,
            'currency' => 'NZD',
        ]);

        $this->assertInstanceOf(Price::class, $invoice->getPrice());
        $this->assertEquals(123.45, $invoice->getPrice()->getValue());
        $this->assertEquals('NZD', $invoice->getPrice()->getCurrency());

        // Change the Amount
        $invoice->setAmount(99.95);
        $this->assertEquals(99.95, $invoice->getPrice()->getValue());
        $this->assertEquals('NZD', $invoice->getPrice()->getCurrency());
    }

    public function testInvoiceLineItems(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->getEntity(Invoice::class, ['id' => 1]);

        /** @var InvoiceLineItem $invoiceLineItem1 */
        $invoiceLineItem1 = $this->getEntity(InvoiceLineItem::class, [
            'id' => 11,
            'summary' => 'Line Item A',
            'amount' => 101.11,
            'currency' => 'USD',
        ]);

        /** @var InvoiceLineItem $invoiceLineItem2 */
        $invoiceLineItem2 = $this->getEntity(InvoiceLineItem::class, [
            'id' => 12,
            'summary' => 'Line Item B',
            'amount' => 51.23,
            'currency' => 'USD',
        ]);

        // Has no line items yet
        $this->assertEmpty($invoice->getLineItems());
        $this->assertFalse($invoice->hasLineItem($invoiceLineItem1));

        // Add Line Items
        $invoice->addLineItem($invoiceLineItem1);
        $invoice->addLineItem($invoiceLineItem2);

        // Has 2 Line Items
        $this->assertNotEmpty($invoice->getLineItems());
        $this->assertCount(2, $invoice->getLineItems());
        $this->assertTrue($invoice->hasLineItem($invoiceLineItem1));

        // Validate first Line Item
        $lineItems = $invoice->getLineItems();
        $this->assertContainsOnlyInstancesOf(InvoiceLineItem::class, $lineItems);
        $this->assertEquals($invoice->getId(), $invoiceLineItem1->getInvoice()->getId());
        $this->assertEquals($invoiceLineItem1, $lineItems->first());

        // Check Line Item Price
        $this->assertInstanceOf(Price::class, $lineItems->first()->getPrice());
        $this->assertEquals(101.11, $lineItems->first()->getPrice()->getValue());
        $this->assertEquals('USD', $lineItems->first()->getPrice()->getCurrency());
    }

    /**
     * @dataProvider invoiceBalancesDataProvider
     */
    public function testInvoiceBalances(
        float $amount,
        float $amountPaid,
        float $expectedBalance,
        bool $expectedPaidState,
    ): void {
        /** @var Invoice $invoice */
        $invoice = $this->getEntity(Invoice::class, [
            'amount' => $amount,
            'amountPaid' => $amountPaid,
        ]);

        $this->assertEquals($expectedBalance, $invoice->getBalance());
        $this->assertEquals($expectedPaidState, $invoice->isBalancePaid());
    }

    /**
     * @return \Generator<string,array<int|float|bool|null>>
     */
    public function invoiceBalancesDataProvider(): \Generator
    {
        yield 'Unpaid Invoice' => [
            'amount' => 123.45,
            'amountPaid' => 0.00,
            'balance' => 123.45,
            'expectedPaidState' => false,
        ];

        yield 'Partially Paid Invoice' => [
            'amount' => 45.67,
            'amountPaid' => 23.21,
            'balance' => 22.46,
            'expectedPaidState' => false,
        ];

        yield 'Fully Paid Invoice' => [
            'amount' => 987.65,
            'amountPaid' => 987.6500,
            'balance' => 0.00,
            'expectedPaidState' => true,
        ];

        yield 'Overpaid Invoice' => [
            'amount' => 12123.51,
            'amountPaid' => 12197.65,
            'balance' => -74.14,
            'expectedPaidState' => true,
        ];
    }
}
