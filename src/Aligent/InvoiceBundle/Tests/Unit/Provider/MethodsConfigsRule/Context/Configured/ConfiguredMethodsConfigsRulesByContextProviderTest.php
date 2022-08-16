<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\Configured;

use Aligent\InvoiceBundle\Provider\MethodsConfigsRule\Context\Configured\ConfiguredMethodsConfigsRulesByContextProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class ConfiguredMethodsConfigsRulesByContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    protected PaymentMethodsConfigsRuleRepository|MockObject $repository;
    protected ConfigManager|MockObject $configManager;
    protected MethodsConfigsRulesFiltrationServiceInterface|MockObject $filtrationService;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(PaymentMethodsConfigsRuleRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->filtrationService = $this->getMockBuilder(MethodsConfigsRulesFiltrationServiceInterface::class)
            ->onlyMethods(['getFilteredPaymentMethodsConfigsRules'])
            ->getMock();
    }

    /**
     * @dataProvider getPaymentMethodConfigs
     * @param array<int> $enabledPaymentMethods
     * @param array<PaymentMethodsConfigsRule> $expectedMethodConfigRules
     * @return void
     */
    public function testGetPaymentMethodsConfigsRules(
        array $enabledPaymentMethods,
        array $expectedMethodConfigRules,
    ): void {
        $website = $this->getEntity(Website::class);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('aligent_invoice.invoices_payment_methods', false, false, $website)
            ->willReturn($enabledPaymentMethods);

        $provider = $this->getMockBuilder(ConfiguredMethodsConfigsRulesByContextProvider::class)
            ->onlyMethods(['getMethodConfigRulesById'])
            ->setConstructorArgs([
                $this->repository,
                $this->configManager,
                $this->filtrationService,
            ])
            ->getMock();

        if (empty($enabledPaymentMethods)) {
            // These should not be called as no payment methods were enabled in configuration
            $provider->expects($this->never())->method('getMethodConfigRulesById');
            $this->filtrationService->expects($this->never())->method('getFilteredPaymentMethodsConfigsRules');
        } else {
            // We expect both methods to be called once each
            $provider->expects($this->once())
                ->method('getMethodConfigRulesById')
                ->willReturn($expectedMethodConfigRules);
            $this->filtrationService->expects($this->once())
                ->method('getFilteredPaymentMethodsConfigsRules')
                ->willReturn($expectedMethodConfigRules);
        }

        $paymentContext = new PaymentContext([
            'website' => $website,
        ]);

        $paymentMethods = $provider->getPaymentMethodsConfigsRules($paymentContext);

        $this->assertEquals($expectedMethodConfigRules, $paymentMethods);
    }

    /**
     * @return \Generator<string,array<string,mixed>>
     */
    public function getPaymentMethodConfigs(): \Generator
    {
        yield 'No Methods enabled' => [
            'enabledPaymentMethods' => [],
            'expectedMethodConfigRules' => [],
        ];

        yield 'One Methods enabled' => [
            'enabledPaymentMethods' => [1],
            'expectedMethodConfigRules' => [
                $this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 1]),
            ],
        ];

        yield 'Three Methods enabled' => [
            'enabledPaymentMethods' => [2, 3, 4],
            'expectedMethodConfigRules' => [
                $this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 2]),
                $this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 3]),
                $this->getEntity(PaymentMethodsConfigsRule::class, ['id' => 4]),
            ],
        ];
    }
}
