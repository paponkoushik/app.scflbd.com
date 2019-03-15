<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class MoreColumnsInCompanyTable
 */
class MoreColumnsInCompanyTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies')
            ->addColumn('company_name_one', 'string', ['null' => false, 'limit' => 128])
            ->addColumn('company_name_two', 'string', ['null' => true, 'limit' => 128])
            ->addColumn('company_name_three', 'string', ['null' => true, 'limit' => 128])
            ->addColumn('company_name_four', 'string', ['null' => true, 'limit' => 128])
            ->update();
    }
}
