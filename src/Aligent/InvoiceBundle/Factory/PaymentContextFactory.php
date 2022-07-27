<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Factory;

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class PaymentContextFactory
{
    protected PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory;

    public function __construct(PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory)
    {
        $this->paymentContextBuilderFactory = $paymentContextBuilderFactory;
    }

    public function create(InvoicePayment $payment): PaymentContextInterface
    {
        $paymentContextBuilder = $this->paymentContextBuilderFactory->createPaymentContextBuilder(
            $payment,
            (string) $payment->getId()
        );

        $subTotal = $payment->getPrice();

        $paymentContextBuilder
            ->setSubTotal($subTotal)
            ->setCurrency($subTotal->getCurrency())
            ->setTotal($subTotal->getValue());

        $website = $payment->getCustomerUser()->getWebsite();

        $paymentContextBuilder->setWebsite($website);

        if (null !== $payment->getCustomer()) {
            $customer = $payment->getCustomer();
            $paymentContextBuilder
                ->setCustomer($customer)
                ->setCustomerUser($payment->getCustomerUser());

            // TODO: Remove this hardcoded value
            //       We should provide a drop down of all billing addresses available to the customer.
            //       We will likely need to create a 'PaymentAddress' entity
            //       and relate that to out payment entity and copy over on submit like the checkout does for orders
            $billingAddress = $customer->getAddressByTypeName(AddressType::TYPE_BILLING);
            if ($billingAddress) {
                $paymentContextBuilder->setBillingAddress($billingAddress);
            }
        }

        return $paymentContextBuilder->getResult();
    }
}
