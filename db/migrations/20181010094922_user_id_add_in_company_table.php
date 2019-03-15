<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class UserIdAddInCompanyTable
 */
class UserIdAddInCompanyTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies')
            ->addColumn('user_id', 'integer')
            ->addForeignKey('user_id', 'scfl_users', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();

    }
}
