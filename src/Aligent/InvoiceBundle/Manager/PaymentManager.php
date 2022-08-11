<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Manager;

use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Oro\Bundle\PaymentBundle\Action\AbstractPaymentMethodAction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ChainProcessor\Context;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyPath;

class PaymentManager
{
    const RESPONSE_PATH = 'response';

    protected PaymentMethodProviderInterface $paymentMethodProvider;
    protected PaymentTransactionProvider $paymentTransactionProvider;
    protected Router $router;
    protected LoggerInterface|NullLogger|null $logger;

    /**
     * @var AbstractPaymentMethodAction[]
     */
    protected array $actions = [];

    /**
     * @var array<string,mixed>
     */
    protected array $response = [];

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentTransactionProvider $paymentTransactionProvider,
        Router $router,
        LoggerInterface $logger = null
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->router = $router;

        if (!$logger) {
            $logger = new NullLogger();
        }

        $this->logger = $logger;
    }

    public function processPayment(InvoicePayment $payment, Request $request): void
    {
        $identifier = $payment->getPaymentMethod();

        if (!$this->paymentMethodProvider->hasPaymentMethod($identifier)) {
            $this->logger->critical(
                'Unsupported Payment method used.',
                [
                    'payment' => $payment,
                    'request' => $request
                ]
            );

            return;
        }

        $method = $this->paymentMethodProvider->getPaymentMethod($identifier);

        if ($method->supports(PaymentMethodInterface::VALIDATE)) {
            $context = $this->buildContext(PaymentMethodInterface::VALIDATE, $payment, $request);
            $action = $this->actions[PaymentMethodInterface::VALIDATE];
            $action->initialize($context->toArray());
            $action->execute($context);

            $paymentTransaction = $this
                ->paymentTransactionProvider
                ->getActiveValidatePaymentTransaction($identifier);

            if (!$paymentTransaction->isSuccessful()) {
                $this->response = $context->get(self::RESPONSE_PATH);

                $this->logger->warning(
                    'Payment method failed validation.',
                    [
                        'payment' => $payment,
                        'request' => $request,
                        'response' => $this->response
                    ]
                );

                return;
            }
        }

        $context = $this->buildContext(PaymentMethodInterface::PURCHASE, $payment, $request);
        $action = $this->actions[PaymentMethodInterface::PURCHASE];
        $action->initialize($context->toArray());
        $action->execute($context);

        $this->response = $context->get(self::RESPONSE_PATH);
    }

    public function addAction(string $actionKey, AbstractPaymentMethodAction $action): void
    {
        $this->actions[$actionKey] = $action;
    }

    /**
     * Builds the context object and options array used when executing actions
     * @param string $type
     * @param InvoicePayment $payment
     * @param Request $request
     * @return Context<string,mixed>
     */
    public function buildContext(string $type, InvoicePayment $payment, Request $request): Context
    {
        $successUrl = $this->router->generate('aligent_invoice_frontend_payment_success', ['id' => $payment->getId()]);
        $failureUrl = $this->router->generate('aligent_invoice_frontend_payment_error', ['id' => $payment->getId()]);

        $frontendPayment = $request->get('aligent_invoice_payment');

        $options = [
            'attribute' => new PropertyPath(self::RESPONSE_PATH),
            'paymentMethod' => $payment->getPaymentMethod(),
            'object' => $payment,
            'transactionOptions' => [
                'saveForLaterUse' => (bool) $request->get('save_for_later'),
                'successUrl' => $successUrl,
                'failureUrl' => $failureUrl,
                'paymentId' => $payment->getId(),
                'additionalData' => $frontendPayment['additional_data'],
            ]
        ];

        if ($type == PaymentMethodInterface::PURCHASE) {
            $options['amount'] = $payment->getTotal();
            $options['currency'] = $payment->getCurrency();
        }

        $context = new Context();

        foreach ($options as $key => $option) {
            $context->set($key, $option);
        }

        return $context;
    }

    public function hasResponse(): bool
    {
        return !empty($this->response);
    }

    /**
     * @return array<string,mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Was the last Response a successful one?
     */
    public function isSuccessful(): bool
    {
        $response = $this->getResponse();
        return (isset($response['successful']) && $response['successful'] === true);
    }
}
