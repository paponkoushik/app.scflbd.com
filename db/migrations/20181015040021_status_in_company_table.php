<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class StatusInCompanyTable
 */
class StatusInCompanyTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies')
            ->addColumn('status', 'string', ['default' => 'active', 'limit' => 20])
            ->update();
    }
}
