<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Entity\Repository;

use Aligent\InvoiceBundle\Entity\Invoice;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * @extends EntityRepository<Invoice>
 */
class InvoiceRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param array<int> $invoiceIds
     * @param array<string> $statusIds
     * @return array<Invoice>
     */
    public function getCurrentCustomerInvoices(
        AclHelper $aclHelper,
        array $invoiceIds = [],
        array $statusIds = [],
    ): array {
        $qb = $this->getFilteredInvoicesQueryBuilder($invoiceIds, $statusIds);

        // Restrict QueryBuilder with ACL
        $query = $aclHelper->apply($qb, BasicPermission::VIEW, [AclHelper::CHECK_RELATIONS => true]);

        return $query->getResult();
    }

    /**
     * @param \DateTime $dueDate Filter to Invoices with Due Date < $dueDate
     * @return array<Invoice>
     */
    public function getOverdueInvoices(\DateTime $dueDate): array
    {
        // Load all Unpaid Invoices
        $qb = $this->getFilteredInvoicesQueryBuilder([], Invoice::UNPAID_STATUSES);

        // Filter to only overdue Invoices
        // NOTE: Only due dates which are BEFORE (<) the provided date.
        //       (Invoices due Today are not Overdue yet)
        $qb
            ->andWhere('i.dueDate < :dueDate')
            ->setParameter('dueDate', $dueDate, Types::DATETIME_MUTABLE);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int> $invoiceIds
     * @param array<string> $statusIds
     * @return QueryBuilder
     */
    public function getFilteredInvoicesQueryBuilder(
        array $invoiceIds = [],
        array $statusIds = [],
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('i');

        if (!empty($invoiceIds)) {
            /**
             * Restrict query to only provided Invoice IDs
             */
            $qb
                ->andWhere('i.id IN(:invoiceIds)')
                ->setParameter('invoiceIds', $invoiceIds);
        }

        if (!empty($statusIds)) {
            /**
             * Restrict query to only provided Invoice Status (enum) IDs
             */
            $qb
                ->andWhere('i.status IN(:statusIds)')
                ->setParameter('statusIds', $statusIds);
        }

        return $qb;
    }
}
