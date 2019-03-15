<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class MyCompaniesTable
 */
class MyCompaniesTable extends AbstractMigration
{
    public function change()
    {
        $this->table('my_companies')
            ->addColumn("uuid", "uuid")
            ->addColumn('user_id', 'integer')
            ->addColumn("name_of_company", "string", ['null' => false, 'limit' => 128])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('order_submitted_at', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addColumn('status', 'string', ['null' => false, 'default' => 'active', 'limit' => 11])
            ->addIndex("uuid", ['unique' => true])
            ->addIndex("name_of_company", ['unique' => true])
            ->create();
    }
}
