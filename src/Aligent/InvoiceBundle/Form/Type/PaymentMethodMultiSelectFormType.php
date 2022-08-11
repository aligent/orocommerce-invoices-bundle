<?php
/**
 * @category  Aligent
 * @author    Bruno Pasqualini <bruno.pasqualini@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Form\Type;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodMultiSelectFormType extends AbstractType
{
    const NAME = 'aligent_invoice_payment_methods';

    protected PaymentMethodProviderInterface $methodProvider;
    protected PaymentMethodViewProviderInterface $methodViewProvider;

    public function __construct(
        PaymentMethodProviderInterface $methodProvider,
        PaymentMethodViewProviderInterface $methodViewProvider,
    ) {
        $this->methodProvider = $methodProvider;
        $this->methodViewProvider = $methodViewProvider;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'multiple' => true,
                'required' => false,
            ]
        );

        $resolver->setNormalizer(
            'choices',
            function (OptionsResolver $options) {
                return $this->getChoices();
            }
        );
    }

    /**
     * Return the payment method options for selection
     * @return array<string,string>
     */
    private function getChoices(): array
    {
        $result = [];
        foreach ($this->methodProvider->getPaymentMethods() as $method) {
            $methodId = $method->getIdentifier();
            $label = $this
                ->methodViewProvider->getPaymentMethodView($methodId)
                ->getAdminLabel();

            $result[$label] = $methodId;
        }

        return $result;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
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
