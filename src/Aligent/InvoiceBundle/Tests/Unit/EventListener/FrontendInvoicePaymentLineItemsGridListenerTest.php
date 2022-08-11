<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Tests\Unit\EventListener;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\EventListener\FrontendInvoicePaymentLineItemsGridListener;
use Aligent\InvoiceBundle\Factory\InvoicePaymentFactory;
use Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider;
use Aligent\InvoiceBundle\Tests\Unit\Entity\Stub\Invoice as InvoiceStub;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FrontendInvoicePaymentLineItemsGridListenerTest extends TestCase
{
    use EntityTrait;

    /**
     * @dataProvider getInvoiceDataWithDueDates
     * @param array<int,array<string,mixed>> $invoiceData
     * @param int $expectedInvoiceCount
     * @param array<int> $expectedInvoicesIds
     * @return void
     * @throws \Exception
     */
    public function testInvoicesAreSortedByDueDate(
        array $invoiceData,
        int $expectedInvoiceCount,
        array $expectedInvoicesIds,
    ): void {
        $datagrid = $this->getMockBuilder(Datagrid::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDatasource','getParameters'])
            ->getMock();

        $invoices = [];
        foreach ($invoiceData as $invoiceDatum) {
            $invoices[] = $this->getEntity(InvoiceStub::class, [
                'id' => $invoiceDatum['id'],
                'dueDate' => new \DateTime($invoiceDatum['dueDate']),
                // Status is mandatory
                'status' => $this->getMockForInvoiceStatus(Invoice::STATUS_OPEN),
            ]);
        }

        $frontendInvoiceProvider = $this->getMockBuilder(FrontendInvoiceProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentCustomerUnpaidInvoices'])
            ->getMock();
        $frontendInvoiceProvider->expects($this->any())
            ->method('getCurrentCustomerUnpaidInvoices')
            ->willReturn($invoices);

        $parameters = $this->getMockBuilder(ParameterBag::class)
            ->onlyMethods(['get'])
            ->getMock();
        $parameters->expects($this->any())
            ->method('get')
            ->with('invoicePayment')
            ->willReturn($this->getEntity(InvoicePayment::class));

        $datagrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn(new ArrayDatasource());
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn($parameters);

        $listener = new FrontendInvoicePaymentLineItemsGridListener(
            $this->createMock(InvoicePaymentFactory::class),
            $frontendInvoiceProvider,
        );

        // Fire event
        $event = new BuildAfter($datagrid);
        $listener->onBuildAfter($event);

        /** @var ArrayDatasource $datasource */
        $datasource = $event->getDatagrid()->getDatasource();
        $this->assertInstanceOf(ArrayDatasource::class, $datasource);

        $invoices = $datasource->getArraySource();
        $this->assertIsArray($invoices);
        $this->assertCount($expectedInvoiceCount, $invoices);

        $this->assertArrayHasKey('id', $invoices[0]);
        $this->assertArrayHasKey('dueDate', $invoices[0]);

        foreach ($expectedInvoicesIds as $key => $invoiceId) {
            // Confirm that the Datagrid Array invoices are
            // in the same order as $expectedInvoiceIds
            // (ie the Invoices are now sorted by Due Date)
            $this->assertEquals($invoiceId, $invoices[$key]['id']);
        }
    }

    /**
     * @return \Generator<string,array<string,int|array<int,mixed>>>
     */
    public function getInvoiceDataWithDueDates(): \Generator
    {
        yield 'Five invoices with different due dates' => [
            'invoiceData' => [
                ['id' => 12, 'dueDate' => '2022-04-01'],
                ['id' => 34, 'dueDate' => '2022-03-30'],
                ['id' => 56, 'dueDate' => '2022-05-01'],
                ['id' => 78, 'dueDate' => '2021-01-01'],
                ['id' => 90, 'dueDate' => '2022-12-31'],
            ],
            'expectedInvoiceCount' => 5,
            'expectedInvoicesIds' => [
                // In order of ascending Due Date (the oldest first)
                78,
                34,
                12,
                56,
                90,
            ],
        ];
    }

    protected function getMockForInvoiceStatus(string $status): MockObject|AbstractEnumValue
    {
        $invoiceStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $invoiceStatus->expects($this->any())
            ->method('getId')
            ->willReturn($status);

        return $invoiceStatus;
    }
}
