<?php


use Phinx\Migration\AbstractMigration;

class ChangeInvoiceTables extends AbstractMigration
{
    public function up()
    {
        $this->table("scfl_invoices")
            ->dropForeignKey("invoice_item_id")
            ->update();

        $this->table("scfl_invoices")->removeColumn("invoice_item_id")
            ->update();

        $this->table("scfl_invoice_items")
            ->addColumn("invoice_id", "integer")
            ->addForeignKey("invoice_id", "scfl_invoices", "id", ['delete' => 'CASCADE', 'update' => "NO_ACTION"])
            ->update();
    }

    public function down()
    {
        $this->table('scfl_invoices')
            ->addColumn('invoice_item_id', 'integer')
            ->addForeignKey('invoice_item_id', 'scfl_invoice_items', 'id', ['delete' => 'cascade', 'update' => 'no_action'])
            ->update();

        $this->table("scfl_invoice_items")
            ->dropForeignKey("invoice_id")
            ->update();

        $this->table("scfl_invoice_items")
            ->removeColumn("invoice_id")
            ->update();
    }
}
