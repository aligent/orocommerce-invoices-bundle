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

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Event\InvoicePaymentSuccessEvent;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\Math\BigDecimal;

class RecalculateInvoicePaidAmountsListener
{
    protected ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function recalculate(InvoicePaymentSuccessEvent $event): void
    {
        // Successful payment, mark InvoicePayment as inactive to 'lock' it
        $invoicePayment = $event->getInvoicePayment();
        $invoicePayment->setActive(false);

        $em = $this->registry->getManagerForClass(Invoice::class);
        $em->persist($invoicePayment);

        foreach ($invoicePayment->getLineItems() as $lineItem) {
            $invoice = $lineItem->getInvoice();
            $amountPaid = $lineItem->getAmount();

            /**
             * Calculate the new Amount Paid and assign back to Invoice
             */
            $newAmountPaid = BigDecimal::of($invoice->getAmountPaid())
                ->plus($amountPaid)
                ->toFloat();
            $invoice->setAmountPaid($newAmountPaid);

            /**
             * If Invoice Balance has been paid, update Invoice Status to 'Paid'
             */
            if ($invoice->isBalancePaid()) {
                $invoice->setStatus($this->getStatus(Invoice::STATUS_PAID));
            }

            $em->persist($invoice);
        }

        $em->flush();
    }

    protected function getStatus(string $statusId): ?AbstractEnumValue
    {
        $className = ExtendHelper::buildEnumValueClassName(Invoice::STATUS_ENUM_CODE);

        return $this->getRepositoryForEnumClass($className)?->find($statusId);
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return EnumValueRepository|null
     */
    protected function getRepositoryForEnumClass(string $className): ?EnumValueRepository
    {
        $repo = $this->registry->getRepository($className);

        if (!$repo instanceof EnumValueRepository) {
            return null;
        }

        return $repo;
    }
}
