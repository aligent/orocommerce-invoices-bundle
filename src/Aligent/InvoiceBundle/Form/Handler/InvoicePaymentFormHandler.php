<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Form\Handler;

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Event\InvoicePaymentSuccessEvent;
use Aligent\InvoiceBundle\Manager\PaymentManager;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class InvoicePaymentFormHandler extends FormHandler
{
    protected PaymentManager $paymentManager;

    /**
     * @param mixed $data
     * @param FormInterface<mixed> $form
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function process(mixed $data, FormInterface $form, Request $request): bool
    {
        $updated = parent::process($data, $form, $request);

        if ($updated && $data instanceof InvoicePayment) {
            $this->paymentManager->processPayment($data, $request);

            if ($this->paymentManager->isSuccessful()) {
                // Successful payment, fire the InvoicePaymentSuccessEvent
                $event = new InvoicePaymentSuccessEvent(
                    $data,
                    $this->paymentManager->getResponse()
                );

                $this->eventDispatcher->dispatch($event, InvoicePaymentSuccessEvent::NAME);
            }
        }

        // Always return false as we don't want the form handler
        // controller our return values
        return false;
    }

    public function setPaymentManager(PaymentManager $paymentManager): void
    {
        $this->paymentManager = $paymentManager;
    }
}
