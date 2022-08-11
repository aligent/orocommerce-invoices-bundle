<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Event;

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Symfony\Contracts\EventDispatcher\Event;

class InvoicePaymentSuccessEvent extends Event
{
    const NAME = 'aligent.invoice.payment_success';

    protected InvoicePayment $invoicePayment;

    /**
     * @var array<string,mixed>
     */
    protected array $response;

    /**
     * @param InvoicePayment $invoicePayment
     * @param array<string,mixed> $paymentResponse
     */
    public function __construct(
        InvoicePayment $invoicePayment,
        array $paymentResponse
    ) {
        $this->invoicePayment = $invoicePayment;
        $this->response = $paymentResponse;
    }

    public function getInvoicePayment(): InvoicePayment
    {
        return $this->invoicePayment;
    }

    public function setInvoicePayment(InvoicePayment $invoicePayment): InvoicePaymentSuccessEvent
    {
        $this->invoicePayment = $invoicePayment;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param array<string,mixed> $response
     */
    public function setResponse(array $response): InvoicePaymentSuccessEvent
    {
        $this->response = $response;

        return $this;
    }
}
