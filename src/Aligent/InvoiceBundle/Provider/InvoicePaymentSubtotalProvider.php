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
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Contracts\Translation\TranslatorInterface;

class InvoicePaymentSubtotalProvider extends AbstractSubtotalProvider implements
    SubtotalProviderInterface
{
    const TYPE = 'invoice_payment_subtotal';
    const SUBTOTAL_SORT_ORDER = 200;

    protected TranslatorInterface $translator;

    public function __construct(
        SubtotalProviderConstructorArguments $arguments,
        TranslatorInterface $translator,
    ) {
        parent::__construct($arguments);
        $this->translator = $translator;
    }

    public function getSubtotal(mixed $entity): ?Subtotal
    {
        if (!$this->isSupported($entity)) {
            throw new \InvalidArgumentException('Entity not supported for provider');
        }

        /** @var InvoicePayment $entity */
        $subtotal = new Subtotal();
        $subtotal
            ->setType(self::TYPE)
            ->setSortOrder(self::SUBTOTAL_SORT_ORDER)
            ->setLabel($this->translator->trans('aligent.invoice.invoicepayment.subtotal.label'))
            ->setVisible(true)
            ->setCurrency($entity->getCurrency())
            ->setAmount($entity->getAmount());

        return $subtotal;
    }

    public function isSupported(mixed $entity): bool
    {
        return $entity instanceof InvoicePayment;
    }
}
