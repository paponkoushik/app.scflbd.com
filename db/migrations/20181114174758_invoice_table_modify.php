<?php


use Phinx\Migration\AbstractMigration;

class InvoiceTableModify extends AbstractMigration
{
    public function change()
    {
        $this->table("scfl_invoices")
            ->addColumn("status", "string", ['default' => 'unpaid', 'limit' => 20])
            ->update();
    }
}
