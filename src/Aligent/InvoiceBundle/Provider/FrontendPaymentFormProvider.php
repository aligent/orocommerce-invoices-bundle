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

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Factory\InvoicePaymentFactory;
use Aligent\InvoiceBundle\Form\Type\InvoicePaymentType;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FrontendPaymentFormProvider extends AbstractFormProvider
{
    protected InvoicePaymentFactory $invoicePaymentFactory;

    public function getPaymentFormView(InvoicePayment $invoicePayment): FormView
    {
        return $this->getFormView(InvoicePaymentType::class, $invoicePayment);
    }

    /**
     * @param InvoicePayment $invoicePayment
     * @return FormInterface<mixed>
     * @throws \Exception
     */
    public function getPaymentForm(InvoicePayment $invoicePayment): FormInterface
    {
        return $this->getForm(InvoicePaymentType::class, $invoicePayment);
    }

    public function setInvoicePaymentFactory(InvoicePaymentFactory $invoicePaymentFactory): void
    {
        $this->invoicePaymentFactory = $invoicePaymentFactory;
    }
}
