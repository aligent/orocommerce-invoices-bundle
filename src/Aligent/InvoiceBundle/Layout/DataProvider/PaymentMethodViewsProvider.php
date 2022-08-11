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

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

class PaymentMethodViewsProvider
{
    protected PaymentMethodViewProviderInterface $paymentMethodViewProvider;

    protected ApplicablePaymentMethodsProvider $paymentMethodProvider;

    public function __construct(
        PaymentMethodViewProviderInterface $paymentMethodViewProvider,
        ApplicablePaymentMethodsProvider $paymentMethodProvider
    ) {
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    /**
     * @param PaymentContextInterface $context
     * @return array<string,array<string,mixed>>
     */
    public function getViews(PaymentContextInterface $context): array
    {
        $paymentMethodViews = [];

        $methods = $this->paymentMethodProvider->getApplicablePaymentMethods($context);

        if (count($methods) !== 0) {
            // Builds a list of payment method identifiers from Oro's applicable methods
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnCallbackFunctions
            $methodIdentifiers = array_map(function (PaymentMethodInterface $method) {
                return $method->getIdentifier();
            }, $methods);

            $views = $this->paymentMethodViewProvider->getPaymentMethodViews($methodIdentifiers);
            foreach ($views as $view) {
                $paymentMethodViews[$view->getPaymentMethodIdentifier()] = [
                    'label' => $view->getLabel(),
                    'block' => $view->getBlock(),
                    'options' => $view->getOptions($context),
                ];
            }
        }

        return $paymentMethodViews;
    }
}
