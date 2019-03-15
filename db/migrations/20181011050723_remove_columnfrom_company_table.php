<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class RemoveColumnfromCompanyTable
 */
class RemoveColumnfromCompanyTable extends AbstractMigration
{
    public function up()
    {
        $this->table('scfl_companies')
            ->removeColumn('name')
            ->save();
    }
}
