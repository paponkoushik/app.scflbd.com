<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class CompanyIdinDirectorsTable
 */
class CompanyIdinDirectorsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies_directors')
            ->addColumn('company_id', 'integer')
            ->addColumn('uuid', 'uuid')
            ->addForeignKey('company_id', 'scfl_companies', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();
    }
}
