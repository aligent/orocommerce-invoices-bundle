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
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

class ConfiguredMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    protected PaymentMethodsConfigsRuleRepository $repository;
    protected ConfigManager $configManager;

    public function __construct(
        PaymentMethodsConfigsRuleRepository $repository,
        ConfigManager $configManager
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
    }

    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context): array
    {
        $availableMethods = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::INVOICE_PAYMENT_METHODS),
            false,
            false,
            $context->getWebsite()
        );

        if (!$availableMethods) {
            return [];
        }

        $qb = $this->createQueryBuilder();

        return $qb->getQuery()->execute(['availableMethods' => $availableMethods]);
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $qb = $this->repository->createQueryBuilder('method');

        $qb->join('method.methodConfigs', 'methodConfigs');

        // This list of Available Methods is built by us from configuration and should be safe
        // phpcs:ignore PHPCS_SecurityAudit.Drupal7.DynQueries.D7DynQueriesDirectVar
        return $qb->where($qb->expr()->in('methodConfigs.type', ':availableMethods'));
    }
}
