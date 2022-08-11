<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Migrations\Schema\v1_1;

use Aligent\InvoiceBundle\Migrations\Schema\v1_0\InvoiceBundleMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class InvoicePaymentsMigration implements
    Migration
{
    const INVOICE_PAYMENT_TABLE = 'aligent_invoice_payment';
    const INVOICE_PAYMENT_LINE_ITEM_TABLE = 'aligent_invoice_payment_line_item';

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createAligentInvoicePaymentTable($schema);
        $this->createAligentInvoicePaymentLineItemTable($schema);

        /** Foreign keys generation **/
        $this->addAligentInvoicePaymentForeignKeys($schema);
        $this->addAligentInvoicePaymentLineItemForeignKeys($schema);
    }

    protected function createAligentInvoicePaymentTable(Schema $schema): void
    {
        $table = $schema->createTable(self::INVOICE_PAYMENT_TABLE);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('amount', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn('payment_method', 'string', ['length' => 255]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('active', 'boolean', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['customer_id'], 'idx_customer_id', []);
        $table->addIndex(['customer_user_id'], 'idx_customer_user_id', []);
    }

    protected function createAligentInvoicePaymentLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable(self::INVOICE_PAYMENT_LINE_ITEM_TABLE);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('invoice_payment_id', 'integer', []);
        $table->addColumn('invoice_id', 'integer', []);
        $table->addColumn('amount', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 255]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['invoice_payment_id', 'invoice_id'], 'alg_inv_pay_to_inv_unq');

        $table->addIndex(['invoice_payment_id'], 'idx_f0c95b75fd1fd325', []);
        $table->addIndex(['invoice_id'], 'idx_f0c95b752989f1fd', []);
    }

    /**
     * @throws SchemaException
     */
    protected function addAligentInvoicePaymentForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable(self::INVOICE_PAYMENT_TABLE);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * @throws SchemaException
     */
    protected function addAligentInvoicePaymentLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable(self::INVOICE_PAYMENT_LINE_ITEM_TABLE);

        $table->addForeignKeyConstraint(
            $schema->getTable(self::INVOICE_PAYMENT_TABLE),
            ['invoice_payment_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable(InvoiceBundleMigration::INVOICE_TABLE_NAME),
            ['invoice_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
