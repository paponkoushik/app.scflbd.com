<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflOrderIdInInvoiceTable
 */
class ScflOrderIdInInvoiceTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_invoices')
            ->addColumn('order_id', 'integer')
            ->addForeignKey('order_id', 'scfl_orders', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();

    }
}
