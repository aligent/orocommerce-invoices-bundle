<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\EventListener;

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Factory\InvoicePaymentFactory;
use Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;

/**
 * Datagrid source which builds data for Payment Line Items datagrid
 *
 * NOTE: We use an array datagrid source so we can use:
 *       \Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider::getCurrentCustomerUnpaidInvoices()
 *       to load the data. It may be possible to rewrite this as a pure ORM query in datagrids.yml in the future,
 *       however this is out of scope for now.
 */
class FrontendInvoicePaymentLineItemsGridListener
{
    protected InvoicePaymentFactory $invoicePaymentFactory;
    protected FrontendInvoiceProvider $frontendInvoiceProvider;

    public function __construct(
        InvoicePaymentFactory $invoicePaymentFactory,
        FrontendInvoiceProvider $frontendInvoiceProvider,
    ) {
        $this->invoicePaymentFactory = $invoicePaymentFactory;
        $this->frontendInvoiceProvider = $frontendInvoiceProvider;
    }

    /**
     * @throws \LogicException
     * @throws \Exception
     */
    public function onBuildAfter(BuildAfter $event): void
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof ArrayDatasource) {
            throw new UnexpectedTypeException($datasource, ArrayDatasource::class);
        }

        $invoicePayment = $this->getParameter($datagrid, 'invoicePayment');

        if (!$invoicePayment instanceof InvoicePayment) {
            throw new UnexpectedTypeException($invoicePayment, InvoicePayment::class);
        }

        $datasource->setArraySource($this->createSourceFromLineItems($invoicePayment));
    }

    /**
     * @param InvoicePayment $invoicePayment
     * @return array<array<string,mixed>>
     */
    protected function createSourceFromLineItems(InvoicePayment $invoicePayment): array
    {
        $source = [];

        $unpaidInvoices = $this
            ->frontendInvoiceProvider
            ->getCurrentCustomerUnpaidInvoices();

        /**
         * Build up a lookup table containing Line Items indexed by Invoice ID
         */
        $lineItemsByInvoice = [];
        foreach ($invoicePayment->getLineItems() as $lineItem) {
            $lineItemsByInvoice[$lineItem->getInvoice()->getId()] = $lineItem;
        }

        foreach ($unpaidInvoices as $invoice) {
            $lineItem = $lineItemsByInvoice[$invoice->getId()] ?? null;
            /**
             * Amount to Pay is either whatever the LineItem had previously,
             * or the Invoice's remaining Balance
             */
            $paymentAmount = $lineItem ? $lineItem->getAmount() : $invoice->getBalance();
            $row = [
                'isEnabled' => !is_null($lineItem),
                'id' => $invoice->getId(),
                'invoice' => $invoice->getInvoiceNo(),
                'amount' => $invoice->getAmount(),
                'amountPaid' => $invoice->getAmountPaid(),
                'balance' => $invoice->getBalance(),
                'paymentAmount' => $paymentAmount,
                'issueDate' => $invoice->getIssueDate(),
                'dueDate' => $invoice->getDueDate(),
                'invoiceStatusId' => $invoice->getStatus()->getId(),
            ];

            $source[] = $row;
        }

        /**
         * Sort by most overdue first
         */
        // Sorting two arrays should be safe enough here
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnCallbackFunctions
        usort($source, function ($a, $b) {
            return $a['dueDate'] <=> $b['dueDate'];
        });

        return $source;
    }

    protected function getParameter(DatagridInterface $datagrid, string $parameterName): mixed
    {
        $value = $datagrid->getParameters()->get($parameterName);

        if ($value === null) {
            throw new \LogicException(sprintf('Parameter "%s" must be set', $parameterName));
        }

        return $value;
    }
}
