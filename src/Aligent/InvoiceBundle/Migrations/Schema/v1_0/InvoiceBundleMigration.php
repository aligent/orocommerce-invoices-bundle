<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Migrations\Schema\v1_0;

use Aligent\InvoiceBundle\Entity\Invoice;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class InvoiceBundleMigration implements
    Migration,
    ExtendExtensionAwareInterface
{
    const INVOICE_TABLE_NAME = 'aligent_invoice';
    const INVOICE_LINE_ITEM_TABLE_NAME = 'aligent_invoice_line_item';

    protected ExtendExtension $extendExtension;

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createAligentInvoiceTable($schema);
        $this->createAligentInvoiceLineItemTable($schema);

        /** Enum field generation **/
        $this->addInvoiceStatusField($schema);

        /** Foreign keys generation **/
        $this->addAligentInvoiceForeignKeys($schema);
        $this->addAligentInvoiceLineItemForeignKeys($schema);
    }

    protected function createAligentInvoiceTable(Schema $schema): void
    {
        $table = $schema->createTable(self::INVOICE_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('invoice_no', 'string', ['length' => 50]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('issue_date', 'date', ['notnull' => false]);
        $table->addColumn('due_date', 'date', ['notnull' => false]);
        $table->addColumn('amount', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('amount_paid', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['invoice_no'], 'uniq_9d7f63e9f11abeb2');

        $table->addIndex(['invoice_no'], 'invoice_no_idx', []);
        $table->addIndex(['customer_id'], 'idx_9d7f63e99395c3f3', []);
        $table->addIndex(['issue_date'], 'issue_date_idx', []);
        $table->addIndex(['due_date'], 'due_date_idx', []);
    }

    protected function createAligentInvoiceLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable(self::INVOICE_LINE_ITEM_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('invoice_id', 'integer', []);
        $table->addColumn('summary', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('amount', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['invoice_id'], 'idx_8cbc78c02989f1fd', []);
    }

    /**
     * @throws SchemaException
     */
    protected function addAligentInvoiceForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable(self::INVOICE_TABLE_NAME);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * @throws SchemaException
     */
    protected function addAligentInvoiceLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable(self::INVOICE_LINE_ITEM_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::INVOICE_TABLE_NAME),
            ['invoice_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    protected function addInvoiceStatusField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            self::INVOICE_TABLE_NAME,
            'status',
            Invoice::STATUS_ENUM_CODE,
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_TRUE],
                'dataaudit' => ['auditable' => true],
                'importexport' => ["order" => 90, "short" => true]
            ]
        );
    }

    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }
}
