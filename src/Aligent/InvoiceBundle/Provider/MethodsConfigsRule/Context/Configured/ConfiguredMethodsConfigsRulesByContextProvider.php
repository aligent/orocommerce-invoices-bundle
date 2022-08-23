<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Provider\MethodsConfigsRule\Context\Configured;

use Aligent\InvoiceBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

class ConfiguredMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    protected PaymentMethodsConfigsRuleRepository $repository;
    protected ConfigManager $configManager;
    protected MethodsConfigsRulesFiltrationServiceInterface $filtrationService;

    public function __construct(
        PaymentMethodsConfigsRuleRepository $repository,
        ConfigManager $configManager,
        MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
        $this->filtrationService = $filtrationService;
    }

    /**
     * Provides list of Payment Method Configs Rules which are enabled in
     * System Configuration. Also filters list using Filtration Service in order
     * to exclude disabled or inapplicable Payment Methods (eg based on Expressions).
     */
    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context): array
    {
        $availableMethodConfigRuleIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::INVOICE_PAYMENT_METHODS),
            false,
            false,
            $context->getWebsite()
        );

        if (!$availableMethodConfigRuleIds) {
            return [];
        }

        $methodConfigRules = $this->getMethodConfigRulesById($availableMethodConfigRuleIds);

        return $this
            ->filtrationService
            ->getFilteredPaymentMethodsConfigsRules($methodConfigRules, $context);
    }

    /**
     * @param array<int> $availableMethodConfigRuleIds
     * @return array<PaymentMethodsConfigsRule>
     */
    protected function getMethodConfigRulesById(array $availableMethodConfigRuleIds): array
    {
        $qb = $this->repository->createQueryBuilder('method');

        // This Expression is safe
        // phpcs:ignore PHPCS_SecurityAudit.Drupal7.DynQueries.D7DynQueriesDirectVar
        $qb
            ->where($qb->expr()->in('method.id', ':ids'))
            ->setParameter('ids', $availableMethodConfigRuleIds);

        return $qb->getQuery()->execute();
    }
}
