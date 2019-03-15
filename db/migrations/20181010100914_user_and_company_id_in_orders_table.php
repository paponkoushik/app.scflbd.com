<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class UserAndCompanyIdInOrdersTable
 */
class UserAndCompanyIdInOrdersTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_orders')
            ->addColumn('user_id', 'integer')
            ->addColumn('company_id', 'integer')
            ->addForeignKey('user_id', 'scfl_users', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->addForeignKey('company_id', 'scfl_companies', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();

    }
}
