<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflInvoicesTable
 */
class ScflInvoicesTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_invoices')
            ->addColumn("uuid", "uuid")
            ->addColumn("invoice_sl", "string", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("total", "integer", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("sub_total", "integer", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex("uuid", ['unique' => true])
            ->create();
    }
}
