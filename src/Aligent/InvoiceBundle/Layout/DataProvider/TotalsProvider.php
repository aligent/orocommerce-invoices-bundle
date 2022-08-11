<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Layout\DataProvider;

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Provider\InvoicePaymentTotalsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalsProvider
{
    protected InvoicePaymentTotalsProvider $totalsProvider;

    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $totals = [];

    public function __construct(InvoicePaymentTotalsProvider $totalsProvider)
    {
        $this->totalsProvider = $totalsProvider;
    }

    /**
     * @param InvoicePayment $invoicePayment
     * @return array<string,mixed>
     */
    public function getData(InvoicePayment $invoicePayment): array
    {
        if (!array_key_exists($invoicePayment->getId(), $this->totals)) {
            $totals = $this->totalsProvider->getTotalsArray($invoicePayment);
            foreach ($totals[TotalProcessorProvider::SUBTOTALS] as $subtotal) {
                if ($subtotal['type'] === 'subtotal') {
                    $totals['subtotal'] = $subtotal;
                    break;
                }
            }
            $this->totals[$invoicePayment->getId()] = $totals;
        }

        return $this->totals[$invoicePayment->getId()];
    }
}
