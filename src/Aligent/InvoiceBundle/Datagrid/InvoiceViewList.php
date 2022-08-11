<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Datagrid;

use Aligent\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;

class InvoiceViewList extends AbstractViewsList
{
    /**
     * @return View[]
     */
    protected function getViewsList(): array
    {
        return [
            $this->createDefaultOpenInvoicesView('Open Invoices'),
            $this->createOverdueInvoicesView('Overdue Invoices'),
            $this->createPaidInvoicesView('Paid Invoices'),
        ];
    }

    protected function createDefaultOpenInvoicesView(string $label): View
    {
        $view = new View(
            'aligent.invoices.open',
            [
                'invoiceStatusId' => [
                    'type'  => EnumFilterType::TYPE_IN,
                    'value' => [
                        Invoice::STATUS_OPEN,
                        Invoice::STATUS_OVERDUE,
                    ],
                ]
            ]
        );

        // This is the default View
        $view->setDefault(true);

        $view->setLabel($label);
        return $view;
    }

    protected function createOverdueInvoicesView(string $label): View
    {
        $view = new View(
            'aligent.invoices.overdue',
            [
                'invoiceStatusId' => [
                    'type'  => EnumFilterType::TYPE_IN,
                    'value' => [
                        Invoice::STATUS_OVERDUE,
                    ],
                ],
            ]
        );

        $view->setLabel($label);
        return $view;
    }


    protected function createPaidInvoicesView(string $label): View
    {
        $view = new View(
            'aligent.invoices.paid',
            [
                'invoiceStatusId' => [
                    'type'  => EnumFilterType::TYPE_IN,
                    'value' => [
                        Invoice::STATUS_PAID,
                    ],
                ],
            ]
        );

        $view->setLabel($label);
        return $view;
    }
}
