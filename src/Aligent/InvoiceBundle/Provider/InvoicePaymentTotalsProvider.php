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
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class InvoicePaymentTotalsProvider
{
    protected TotalProcessorProvider $totalProcessorProvider;

    public function __construct(
        TotalProcessorProvider $totalsProvider,
    ) {
        $this->totalProcessorProvider = $totalsProvider;
    }

    /**
     * @param InvoicePayment $invoicePayment
     * @return array<string,mixed>
     */
    public function getTotalsArray(InvoicePayment $invoicePayment): array
    {
        $this->totalProcessorProvider->enableRecalculation();
        return $this->totalProcessorProvider->getTotalWithSubtotalsAsArray($invoicePayment);
    }
}
