<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class AppSettingsTable
 */
class AppSettingsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('app_settings')
            ->addColumn("smtp _username", "string", ['null' => false, 'limit' => 128])
            ->addColumn("smtp_password", "string", ['null' => false, 'limit' => 128])
            ->addColumn("smtp_default_from_name", "string", ['null' => false, 'limit' => 128])
            ->addColumn("smtp_default_from_email", "string", ['null' => false, 'limit' => 128])
            ->addColumn("smtp_port", "string", ['null' => false, 'limit' => 128])
            ->addColumn("smtp_host", "string", ['null' => false, 'limit' => 128])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

    }
}
