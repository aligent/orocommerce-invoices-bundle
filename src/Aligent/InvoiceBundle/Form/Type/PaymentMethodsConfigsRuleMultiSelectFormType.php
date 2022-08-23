<?php
/**
 * @category  Aligent
 * @author    Bruno Pasqualini <bruno.pasqualini@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Form\Type;

use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentMethodsConfigsRuleMultiSelectFormType extends AbstractType
{
    const NAME = 'aligent_invoice_payment_methods';

    protected PaymentMethodViewProviderInterface $methodViewProvider;
    protected PaymentMethodsConfigsRuleRepository $repository;
    protected TranslatorInterface $translator;

    public function __construct(
        PaymentMethodViewProviderInterface $methodViewProvider,
        PaymentMethodsConfigsRuleRepository $repository,
        TranslatorInterface $translator,
    ) {
        $this->methodViewProvider = $methodViewProvider;
        $this->repository = $repository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ChoiceType::class;
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
        foreach ($this->repository->findAll() as $methodConfigRule) {
            $methodId = $methodConfigRule->getId();
            $label = $methodConfigRule->getRule()->getName();
            $methods = [];
            foreach ($methodConfigRule->getMethodConfigs() as $methodConfig) {
                try {
                    $methods[] = $this
                        ->methodViewProvider->getPaymentMethodView($methodConfig->getType())
                        ->getAdminLabel();
                } catch (\InvalidArgumentException) {
                    // Skip this Method as it's probably linked to a disabled Integration
                }
            }

            $methods = ($methods)
                ? implode(", ", $methods)
                : $this
                    ->translator
                    ->trans('aligent.invoice.system_configuration.fields.invoice_payment_methods.no_methods_available');

            $label .= sprintf(" (%s)", $methods);

            if (!$methodConfigRule->getRule()->isEnabled()) {
                // Highlight disabled Methods
                $label .= $this->translator->trans(
                    'aligent.invoice.system_configuration.fields.invoice_payment_methods.disabled_method_suffix'
                );
            }

            $result[$label] = $methodId;
        }

        return $result;
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
