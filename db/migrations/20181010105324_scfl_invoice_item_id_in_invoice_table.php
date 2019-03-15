<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflInvoiceItemIdInInvoiceTable
 */
class ScflInvoiceItemIdInInvoiceTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_invoices')
            ->addColumn('invoice_item_id', 'integer')
            ->addForeignKey('invoice_item_id', 'scfl_invoice_items', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();

    }
}
