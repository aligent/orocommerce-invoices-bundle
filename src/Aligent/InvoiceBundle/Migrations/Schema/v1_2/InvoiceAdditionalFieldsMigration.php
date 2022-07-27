<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class InvoiceAdditionalFieldsMigration implements
    Migration
{
    const INVOICE_TABLE = 'aligent_invoice';
    const INVOICE_PAYMENT_TABLE = 'aligent_invoice_payment';

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updateAligentInvoiceTable($schema);
        $this->updateAligentInvoicePaymentTable($schema);
    }

    /**
     * @throws SchemaException
     */
    protected function updateAligentInvoiceTable(Schema $schema): void
    {
        $table = $schema->getTable(self::INVOICE_TABLE);

        $table->addColumn('tax_total', 'money', [
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)',
            'default' => 0, // We need to default to 0 as this is a non-nullable column
        ]);
    }

    /**
     * @throws SchemaException
     */
    protected function updateAligentInvoicePaymentTable(Schema $schema): void
    {
        $table = $schema->getTable(self::INVOICE_PAYMENT_TABLE);

        $table->addColumn('total', 'money', [
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)',
            'default' => 0, // We need to default to 0 as this is a non-nullable column
        ]);
    }
}
