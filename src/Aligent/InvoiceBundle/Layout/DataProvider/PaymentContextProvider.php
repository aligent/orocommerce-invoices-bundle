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
use Aligent\InvoiceBundle\Factory\PaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class PaymentContextProvider
{
    protected PaymentContextFactory $paymentContextFactory;

    public function __construct(PaymentContextFactory $paymentContextFactory)
    {
        $this->paymentContextFactory = $paymentContextFactory;
    }

    public function getContext(InvoicePayment $entity): PaymentContextInterface
    {
        return $this->paymentContextFactory->create($entity);
    }
}
