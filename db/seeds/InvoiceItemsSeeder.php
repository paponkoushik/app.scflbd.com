<?php


use Phinx\Seed\AbstractSeed;

class InvoiceItemsSeeder extends AbstractSeed
{
    public function getInvoice()
    {
        return array_map(function ($c) {
            return intval($c['id']);
        }, $this->fetchAll("select id from scfl_invoices"));
    }

    public function run()
    {
        $faker  = Faker\Factory::create();
        $invoices = [];

        foreach ($this->getInvoice() as $invoice) {
            $invoiceExists = $this->fetchRow("select id from scfl_invoice_items where invoice_id = $invoice");

            if(empty($invoiceExists)){
                for ($i = 0; $i < rand(1, 1); $i++) {
                    $invoices[] = [
                        'name' => $faker->name,
                        'price' => $faker->randomFloat(4),
                        'created' => date("Y-m-d H:i:s"),
                        'modified' => date("Y-m-d H:i:s"),
                        'invoice_id' => $invoice
                    ];
                }
            }
        }

        $this->table('scfl_invoice_items')->insert($invoices)->save();
    }
}
