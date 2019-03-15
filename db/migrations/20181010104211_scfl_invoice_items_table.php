<?php


use Phinx\Migration\AbstractMigration;

class ScflInvoiceItemsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_invoice_items')
            ->addColumn("name", "string", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("percentage", "string", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("price", "integer", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->create();

    }
}
