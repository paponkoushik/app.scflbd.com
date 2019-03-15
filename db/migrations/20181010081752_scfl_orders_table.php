<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflOrdersTable
 */
class ScflOrdersTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_orders')
            ->addColumn("uuid", "uuid")
            ->addColumn("orders_sl", "string", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("type", "string", ['default' => "company_register", 'null' => true, 'limit' => 80])
            ->addColumn("total_amount", "integer", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex("uuid", ['unique' => true])
            ->create();
    }
}
