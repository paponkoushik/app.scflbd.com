<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class UuidInCompanyTable
 */
class UuidInCompanyTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies')
            ->addColumn('uuid', 'uuid')
            ->update();

    }
}
