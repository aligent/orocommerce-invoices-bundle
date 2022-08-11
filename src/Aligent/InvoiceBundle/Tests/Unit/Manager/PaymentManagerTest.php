<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Tests\Unit\Manager;

use Aligent\InvoiceBundle\Manager\PaymentManager;
use PHPUnit\Framework\TestCase;

class PaymentManagerTest extends TestCase
{
    /**
     * @dataProvider paymentResponseDataProvider
     * @param array<string,mixed> $response
     * @param bool $expectedSuccess
     * @return void
     */
    public function testPaymentResponsesCanBeAccessed(array $response, bool $expectedSuccess): void
    {
        $paymentManager = $this->getMockBuilder(PaymentManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResponse'])
            ->getMock();

        $paymentManager->expects($this->any())
            ->method('getResponse')
            ->willReturn($response);

        $this->assertEquals($expectedSuccess, $paymentManager->isSuccessful());
    }

    /**
     * @return \Generator<array<string,mixed>>
     */
    public function paymentResponseDataProvider(): \Generator
    {
        yield 'No Response is unsuccessful' => [
            'response' => [],
            'expectedSuccess' => false,
        ];

        yield 'Unsuccessful Response is unsuccessful' => [
            'response' => [
                'successful' => false,
            ],
            'expectedSuccess' => false,
        ];

        yield 'Successful Response is Successful' => [
            'response' => [
                'successful' => true,
            ],
            'expectedSuccess' => true,
        ];
    }
}
