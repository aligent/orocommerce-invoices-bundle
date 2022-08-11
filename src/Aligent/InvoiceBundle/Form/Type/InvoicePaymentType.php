<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Form\Type;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Factory\InvoicePaymentFactory;
use Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoicePaymentType extends AbstractType
{
    const NAME = 'aligent_invoice_payment';

    protected InvoicePaymentFactory $invoicePaymentFactory;
    protected FrontendInvoiceProvider $frontendInvoiceProvider;
    protected TotalProcessorProvider $totalProcessorProvider;

    public function __construct(
        InvoicePaymentFactory $invoicePaymentFactory,
        FrontendInvoiceProvider $frontendInvoiceProvider,
        TotalProcessorProvider $totalProcessorProvider,
    ) {
        $this->invoicePaymentFactory = $invoicePaymentFactory;
        $this->frontendInvoiceProvider = $frontendInvoiceProvider;
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string,mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'id',
                HiddenType::class,
                [
                    // We don't need to map this, it's purely so that the JS can access the ID
                    // for the SaveState feature
                    'mapped' => false,
                ]
            )
            ->add(
                'payment_method',
                HiddenType::class,
                [
                    'mapped' => true,
                    'property_path' => 'paymentMethod'
                ]
            )
            ->add(
                'additional_data',
                HiddenType::class,
                [
                    'mapped' => false,
                ]
            )
            ->add(
                'payment_append_invoices',
                EntityIdentifierType::class,
                [
                    'class'    => Invoice::class,
                    'required' => true,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            /**
             * NOTE: We're not actually using the 'remove invoices' field
             *       but it's required for the JS component to work correctly.
             */
            ->add(
                'payment_remove_invoices',
                EntityIdentifierType::class,
                [
                    'class'    => Invoice::class,
                    'required' => true,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
        ;

        /**
         * Convert PaymentLineItems into list of Invoices and assign back to form fields
         */
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (PostSetDataEvent $event) {
            /** @var InvoicePayment $invoicePayment */
            $invoicePayment = $event->getData();

            /**
             * Set the Payment ID so that the JS can access it for the
             * Save State feature
             */
            $form = $event->getForm();
            $form->get('id')->setData($invoicePayment->getId());

            // We need to assign all Payment Invoices back to the field so that this works during subtree updates
            $form->get('payment_append_invoices')->setData($invoicePayment->getInvoices());
        });

        /**
         * Retrieve list of Invoices from form field (datagrid checkboxes)
         * and convert back into PaymentLineItems
         */
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
            /** @var InvoicePayment $invoicePayment */
            $invoicePayment = $event->getData();
            $extraData = $event->getForm()->getExtraData();

            $invoices = $this->frontendInvoiceProvider->getCurrentCustomerUnpaidInvoices();

            $appendInvoices = $event->getForm()->get('payment_append_invoices')->getData();

            foreach ($invoices as $invoice) {
                $existingInvoice = $invoicePayment->hasInvoice($invoice);
                if (!$existingInvoice && in_array($invoice, $appendInvoices)) {
                    // We ticked the Invoice to add it, but it's not currently assigned to the Payment
                    $this->invoicePaymentFactory->createInvoicePaymentLineItem($invoice, $invoicePayment);
                } elseif ($existingInvoice && !in_array($invoice, $appendInvoices)) {
                    // Payment has this Invoice, but it is no longer ticked
                    $invoicePayment->removeLineItemByInvoice($invoice);
                }
            }

            foreach ($invoicePayment->getLineItems() as $lineItem) {
                // If custom Amount provided, use this instead of Invoice Balance
                $amount = ($extraData['invoices']['amount'][$lineItem->getInvoice()?->getId()]) ?? null;
                if ($amount) {
                    $lineItem->setAmount($amount);
                }
            }

            // We have (possibly) modified the line items, we need to recalculate
            $invoicePayment->recalculateAmount();

            $total = $this->totalProcessorProvider->getTotal($invoicePayment);
            $invoicePayment->setTotal($total->getAmount());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoicePayment::class,
            'allow_extra_fields' => true,
        ]);
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
