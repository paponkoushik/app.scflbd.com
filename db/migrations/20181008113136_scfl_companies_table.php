<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflCompaniesTable
 */
class ScflCompaniesTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies')
            ->addColumn("name", "string", ['null' => false, 'limit' => 128])
            ->addColumn("primary_street", "string", ['null' => false, 'limit' => 128])
            ->addColumn("secondary_street", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("postcode", "string", ['null' => true, 'default' => null, 'limit' => 4])
            ->addColumn("city", "string", ['null' => true, 'default' => null, 'limit' => 40])
            ->addColumn("country", "string", ['null' => true, 'default' => null, 'limit' => 40])
            ->addColumn("authorised_capital", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("paid_up_capital", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("qualification_of_director", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("board_meeting", "string", ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn("agm", "string", ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn("chairman", "string", ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn("managing_director", "string", ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn("power_of_management", "string", ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn("signing_bank_ac", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("business_type", "string", ['null' => true, 'default' => null, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
