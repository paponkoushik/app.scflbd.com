<?php


use Phinx\Migration\AbstractMigration;

/**
 * Class ScflCompaniesDirectorsTable
 */
class ScflCompaniesDirectorsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('scfl_companies_directors')
            ->addColumn("name", "string", ['null' => false, 'limit' => 128])
            ->addColumn("father_or_husband_name", "string", ['null' => true, 'limit' => 128])
            ->addColumn("mother_name", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("present_address", "string", ['null' => true, 'default' => null, 'limit' => 4])
            ->addColumn("mobile_nmm", "string", ['null' => true, 'default' => null, 'limit' => 40])
            ->addColumn("country", "string", ['null' => true, 'default' => null, 'limit' => 40])
            ->addColumn("email_address", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("date_of_birth", "string", ['null' => true, 'default' => null])
            ->addColumn("designation", "string", ['null' => true, 'default' => null, 'limit' => 31])
            ->addColumn("etin_no", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("shares_quantity", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn("nid_password_no", "string", ['null' => true, 'default' => null, 'limit' => 128])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

    }
}
