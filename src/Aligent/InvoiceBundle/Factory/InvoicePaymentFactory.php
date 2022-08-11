<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Factory;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Entity\InvoicePaymentLineItem;
use Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

class InvoicePaymentFactory
{
    protected TokenAccessor $tokenAccessor;
    protected FrontendInvoiceProvider $frontendInvoiceProvider;
    protected ManagerRegistry $registry;

    public function __construct(
        TokenAccessor $tokenAccessor,
        FrontendInvoiceProvider $frontendInvoiceProvider,
        ManagerRegistry $registry,
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->frontendInvoiceProvider = $frontendInvoiceProvider;
        $this->registry = $registry;
    }

    /**
     * Create (and optionally persist) a new InvoicePayment
     * from all the Current Customer's Unpaid Invoices
     * @param bool $persist Should we persist this InvoicePayment?
     * @return InvoicePayment
     * @throws \Exception
     */
    public function createFromUnpaidInvoices(bool $persist = false): InvoicePayment
    {
        $unpaidInvoices = $this->frontendInvoiceProvider->getCurrentCustomerUnpaidInvoices();

        if (empty($unpaidInvoices)) {
            throw new \Exception('No Invoices currently require Payment');
        }
        $invoicePayment = $this->create($unpaidInvoices);

        if ($persist) {
            $em = $this->registry->getManagerForClass(InvoicePayment::class);
            $em->persist($invoicePayment);
            $em->flush();
        }

        return $invoicePayment;
    }

    /**
     * Create an InvoicePayment (assigned to the Current Frontend Customer)
     * with the provided $invoices attached as Line Items
     * @param Invoice[] $invoices
     * @return InvoicePayment
     * @throws \Exception
     */
    public function create(array $invoices): InvoicePayment
    {
        $invoicePayment = new InvoicePayment();

        $customerUser = $this->getCustomerUser();
        if (!$customerUser) {
            throw new \LogicException('The customer user does not exist in the security context.');
        }

        $invoicePayment
            ->setCustomerUser($customerUser)
            ->setCustomer($customerUser->getCustomer())
            ->setActive(true)
            ->setPaymentMethod(''); // NOTE: This column is non-nullable
        ;

        return $this->setInvoices($invoicePayment, $invoices);
    }

    /**
     * Replace an InvoicePayment's Line Items with the provided $invoices
     * @param InvoicePayment $invoicePayment
     * @param Invoice[] $invoices
     * @return InvoicePayment
     * @throws \Exception
     */
    public function setInvoices(InvoicePayment $invoicePayment, array $invoices): InvoicePayment
    {
        $this->validateInvoiceCurrencies($invoices);

        // Empty out the existing Line Items
        $invoicePayment->setLineItems(new ArrayCollection());

        foreach ($invoices as $invoice) {
            $this->createInvoicePaymentLineItem($invoice, $invoicePayment);
        }

        return $invoicePayment;
    }

    /**
     * Creates an InvoicePaymentLineItem
     * from an Invoice with an Optional amount,
     * and adds to the InvoicePayment while also
     * updating the InvoicePayment Amount.
     */
    public function createInvoicePaymentLineItem(
        Invoice $invoice,
        InvoicePayment $invoicePayment,
        int|float $amount = null,
    ): InvoicePaymentLineItem {
        $paymentLineItem = new InvoicePaymentLineItem();

        /**
         * If Amount specified, use this. Or else use Invoice Balance.
         */
        $amount = !is_null($amount) ? $amount : $invoice->getBalance();

        $paymentLineItem
            ->setInvoice($invoice)
            ->setInvoicePayment($invoicePayment)
            ->setAmount($amount)
            ->setCurrency($invoice->getCurrency())
        ;

        // Add Line Item Amount to Payment Total
        $invoicePayment->addLineItem($paymentLineItem);

        return $paymentLineItem;
    }

    /**
     * @param Invoice[] $invoices
     * @return void
     * @throws \Exception
     */
    public function validateInvoiceCurrencies(array $invoices): void
    {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnCallbackFunctions
        $currencies = array_unique(array_map(function (Invoice $invoice) {
            return $invoice->getCurrency();
        }, $invoices));

        if (count($currencies) > 1) {
            throw new \Exception('Multi-Currency Payments are not supported');
        }
    }

    private function getCustomerUser(): ?CustomerUser
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof CustomerUser
            ? $user
            : null;
    }
}
