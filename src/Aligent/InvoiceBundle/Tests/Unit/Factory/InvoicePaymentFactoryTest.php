<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Tests\Unit\Factory;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Entity\InvoicePaymentLineItem;
use Aligent\InvoiceBundle\Factory\InvoicePaymentFactory;
use Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class InvoicePaymentFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    protected InvoicePaymentFactory $factory;
    protected TokenAccessor|MockObject $tokenAccessor;

    protected function setUp(): void
    {
        $customer = $this->getEntity(Customer::class, [
            'id' => 123,
            'name' => 'Test Customer',
        ]);

        $customerUser = $this->getEntity(CustomerUser::class, [
            'id' => 900,
            'customer' => $customer,
            'firstName' => 'Test',
            'lastName' => 'CustomerUser',
        ]);

        $this->tokenAccessor = $this->getMockBuilder(TokenAccessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser'])
            ->getMock();

        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->factory = new InvoicePaymentFactory(
            $this->tokenAccessor,
            $this->createMock(FrontendInvoiceProvider::class),
            $this->createMock(ManagerRegistry::class)
        );
    }

    /**
     * @dataProvider buildMultiCurrencyInvoices
     * @param array<int,mixed> $invoiceData
     * @param array<string,mixed> $expectedPayment
     * @return void
     * @throws \Exception
     */
    public function testInvoicePaymentsCanBeCreated(array $invoiceData, array $expectedPayment): void
    {
        $invoiceCount = count($invoiceData);

        /** @var CustomerUser $currentCustomerUser */
        $currentCustomerUser = $this->tokenAccessor->getUser();

        $invoices = $this->buildInvoices($invoiceData);

        // Force all Invoices to same Currency
        $invoices = $this->setInvoiceCurrency($invoices, 'AUD');

        $invoicePayment = $this->factory->create($invoices);

        $this->assertInstanceOf(InvoicePayment::class, $invoicePayment);
        $this->assertCount($invoiceCount, $invoicePayment->getLineItems());
        $this->assertContainsOnlyInstancesOf(InvoicePaymentLineItem::class, $invoicePayment->getLineItems());

        $this->assertEquals($currentCustomerUser->getCustomer(), $invoicePayment->getCustomer());
        $this->assertEquals($currentCustomerUser, $invoicePayment->getCustomerUser());

        $this->assertInstanceOf(CustomerUser::class, $invoicePayment->getCustomerUser());
        // Assert Payment Total is SUM of invoice balances
        $this->assertEquals($expectedPayment['amount'], $invoicePayment->getAmount());
        // Payment Currency should come from Invoices
        $this->assertEquals('AUD', $invoicePayment->getCurrency());
    }

    /**
     * @dataProvider buildMultiCurrencyInvoices
     * @param array<int,mixed> $invoiceData
     * @return void
     * @throws \Exception
     */
    public function testSingleCurrencyInvoicePaymentsAreAllowed(array $invoiceData): void
    {
        $invoices = $this->buildInvoices($invoiceData);

        /**
         * Set all Invoices to same Currency
         */
        $invoices = $this->setInvoiceCurrency($invoices, 'USD');

        // An exception should NOT be thrown here
        try {
            $this->factory->validateInvoiceCurrencies($invoices);
        } catch (\Exception $e) {
            $this->fail('Single Currency Invoice Payments failed validation');
        }
    }

    /**
     * @dataProvider buildMultiCurrencyInvoices
     * @param array<int,mixed> $invoiceData
     * @return void
     * @throws \Exception
     */
    public function testMultiCurrencyInvoicePaymentsAreRejected(array $invoiceData): void
    {
        $invoices = $this->buildInvoices($invoiceData);

        /**
         * An Exception should be thrown
         */
        $this->expectExceptionMessage('Multi-Currency Payments are not supported');
        $this->factory->validateInvoiceCurrencies($invoices);
    }

    /**
     * @dataProvider buildMultiCurrencyInvoices
     * @param array<int,mixed> $invoiceData
     * @return void
     */
    public function testInvoicePaymentLineItemsCanBeCreated(array $invoiceData): void
    {
        /** @var InvoicePayment $invoicePayment */
        $invoicePayment = $this->getEntity(InvoicePayment::class);

        $invoices = $this->buildInvoices($invoiceData);

        foreach ($invoices as $invoice) {
            $invoicePaymentLineItem = $this->factory->createInvoicePaymentLineItem($invoice, $invoicePayment);
            $this->assertEquals($invoice, $invoicePaymentLineItem->getInvoice());
            $this->assertEquals($invoice->getBalance(), $invoicePaymentLineItem->getAmount());
            $this->assertEquals($invoice->getCurrency(), $invoicePaymentLineItem->getCurrency());
            $this->assertEquals($invoicePayment, $invoicePaymentLineItem->getInvoicePayment());
        }
    }

    /**
     * @dataProvider buildMultiCurrencyInvoices
     * @param array<int,mixed> $invoiceData
     * @return void
     */
    public function testInvoicePaymentLineItemsCanBeCreatedWithCustomAmounts(array $invoiceData): void
    {
        /** @var InvoicePayment $invoicePayment */
        $invoicePayment = $this->getEntity(InvoicePayment::class);

        /**
         * Create One Invoice
         */
        /** @var Invoice $invoice */
        $invoice = current($this->buildInvoices($invoiceData));

        /**
         * We want to pay $1.23 less than the remaining balance
         */
        $amount = $invoice->getBalance() - 1.23;

        $invoicePaymentLineItem = $this->factory->createInvoicePaymentLineItem($invoice, $invoicePayment, $amount);

        $this->assertEquals($amount, $invoicePaymentLineItem->getAmount());
    }

    /**
     * @param array<int,array<string,mixed>> $invoiceData
     * @return Invoice[]
     */
    protected function buildInvoices(array $invoiceData): array
    {
        /** @var Invoice[] $invoices */
        $invoices = [];
        foreach ($invoiceData as $invoiceProperties) {
            $invoices[] = $this->getEntity(
                Invoice::class,
                $invoiceProperties
            );
        }

        return $invoices;
    }

    /**
     * @return \Generator<string,array<string,mixed>>
     */
    public function buildMultiCurrencyInvoices(): \Generator
    {
        yield 'Multiple Currency Invoices' => [
            'invoiceData' => [
                ['id' => 1, 'invoiceNo' => 'INV001', 'amount' => 20.00, 'currency' => 'GBP'],
                ['id' => 2, 'invoiceNo' => 'INV002', 'amount' => 10.00, 'currency' => 'AUD'],
            ],
            'expectedPayment' => [
                // Expected Payment Total (Sum of Invoices)
                'amount' => 30.00,
            ]
        ];

        yield 'Multiple Currency Invoices with duplicate Currency' => [
            'invoiceData' => [
                ['id' => 1, 'invoiceNo' => 'INV001', 'amount' => 20.53, 'currency' => 'NZD'],
                ['id' => 2, 'invoiceNo' => 'INV002', 'amount' => 10.00, 'currency' => 'USD'],
                ['id' => 3, 'invoiceNo' => 'INV003', 'amount' => 50.01, 'currency' => 'NZD'],
            ],
            'expectedPayment' => [
                // Expected Payment (Sum of Invoices)
                'amount' => 80.54,
            ]
        ];

        yield 'Multiple Currency Invoices with Partial Payments Made' => [
            'invoiceData' => [
                ['id' => 1, 'invoiceNo' => 'INV001', 'amount' => 20.00, 'amountPaid' => 1.18, 'currency' => 'GBP'],
                ['id' => 2, 'invoiceNo' => 'INV002', 'amount' => 10.00, 'amountPaid' => 5.06, 'currency' => 'AUD'],
            ],
            'expectedPayment' => [
                // Expected Payment Total (Sum of Invoice Balances)
                'amount' => 23.76,
            ]
        ];
    }

    /**
     * @param Invoice[] $invoices
     * @param string $currency
     * @return Invoice[]
     */
    protected function setInvoiceCurrency(array $invoices, string $currency): array
    {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnCallbackFunctions
        array_map(function (Invoice $invoice) use ($currency) {
            $invoice->setCurrency($currency);
        }, $invoices);

        return $invoices;
    }
}
