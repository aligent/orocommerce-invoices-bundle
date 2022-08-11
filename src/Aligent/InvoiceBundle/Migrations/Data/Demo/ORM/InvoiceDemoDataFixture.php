<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Migrations\Data\Demo\ORM;

use Aligent\FixturesBundle\Fixtures\AbstractAliceFixture;

class InvoiceDemoDataFixture extends AbstractAliceFixture
{
    protected function getFixtures(): string
    {
        return __DIR__ . '/data';
    }
}
