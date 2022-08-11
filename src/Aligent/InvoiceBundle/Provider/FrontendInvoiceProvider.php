<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Provider;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Entity\Repository\InvoiceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FormBundle\Model\UpdateFactory;
use Oro\Bundle\FormBundle\Model\UpdateInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\FormInterface;

class FrontendInvoiceProvider
{
    protected UpdateFactory $updateFactory;
    protected ManagerRegistry $registry;
    protected AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $registry,
        AclHelper $aclHelper,
        UpdateFactory $updateFactory,
    ) {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->updateFactory = $updateFactory;
    }

    /**
     * Return a list of all the currently logged-in Customer's Unpaid Invoices
     * @param array<int> $invoiceIds
     * @return Invoice[]
     */
    public function getCurrentCustomerUnpaidInvoices(array $invoiceIds = []): array
    {
        /** @var InvoiceRepository $repository */
        $repository = $this->getInvoiceRepository();

        return $repository->getCurrentCustomerInvoices(
            $this->aclHelper,
            $invoiceIds,
            Invoice::UNPAID_STATUSES
        );
    }

    /**
     * Return a list of all the currently logged-in Customer's Invoices
     * Provide $invoiceIds to restrict the list only to specific Invoice IDs
     *
     * @param array<int> $invoiceIds
     * @return Invoice[]
     */
    public function getCurrentCustomerInvoices(array $invoiceIds = []): array
    {
        /** @var InvoiceRepository $repository */
        $repository = $this->getInvoiceRepository();

        return $repository->getCurrentCustomerInvoices($this->aclHelper, $invoiceIds);
    }

    /**
     * @return ObjectRepository<Invoice>
     */
    protected function getInvoiceRepository(): ObjectRepository
    {
        return $this->registry->getRepository(Invoice::class);
    }

    /**
     * @param InvoicePayment $invoicePayment
     * @param FormInterface<mixed> $form
     * @return UpdateInterface
     */
    public function createInvoicePaymentFormUpdate(InvoicePayment $invoicePayment, FormInterface $form): UpdateInterface
    {
        return $this->updateFactory->createUpdate($invoicePayment, $form, null, null);
    }
}
