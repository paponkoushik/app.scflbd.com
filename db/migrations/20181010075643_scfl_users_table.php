<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflUsersTable
 */
class ScflUsersTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_users')
            ->addColumn("uuid", "uuid")
            ->addColumn("first_name", "string", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("last_name", "string", ['default' => null, 'null' => true, 'limit' => 80])
            ->addColumn("email_address", "string", ['null' => false, 'limit' => 128])
            ->addColumn('gender', 'string', ['default' => null, 'null' => true, 'limit' => 6])
            ->addColumn("password", "string", ['null' => false])
            ->addColumn("company_name", "string", ['default' => null, 'null' => true, 'limit' => 120])
            ->addColumn("website", "string", ['default' => null, 'null' => true, 'limit' => 120])
            ->addColumn("role", "string", ['null' => false, 'default' => 'client', 'limit' => 11])
            ->addColumn("city", "string", ['default' => null, 'null' => true , 'limit' => 80])
            ->addColumn("phone", "string", ['default' => null, 'null' => true, 'limit' => 11])
            ->addColumn("profile_pic", "string", ['default' => null, 'null' => true, 'limit' => 120])
            ->addColumn("pwd_reset_token", "string", ['default' => null, 'null' => true, 'limit' => 120])
            ->addColumn("address", "string", ['default' => null, 'null' => true, 'limit' => 120])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex("uuid", ['unique' => true])
            ->addIndex("email_address", ['unique' => true])
            ->addIndex("pwd_reset_token", ['unique' => true])
            ->create();

        $this->table("my_companies")
            ->addForeignKey('user_id', 'scfl_users', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();
    }
}
