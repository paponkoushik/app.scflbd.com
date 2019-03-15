<?php


use Phinx\Seed\AbstractSeed;

class InvoiceSeeder extends AbstractSeed
{
    public function getUsers()
    {
        return array_map(function ($c) {
            return intval($c['id']);
        }, $this->fetchAll("select id from scfl_users"));
    }

    public function getOrders()
    {
        return array_map(function ($c) {
            return intval($c['id']);
        }, $this->fetchAll("select id from scfl_orders"));
    }

    public function run()
    {
        $faker  = Faker\Factory::create();
        $orders = [];
        $i      = 12323;

        foreach ($this->getOrders() as $order) {
            $invoiceExists = $this->fetchRow("select id from scfl_invoices where order_id = $order");

            if (empty($invoiceExists)) {
                $orders[] = [
                    'uuid'            => $faker->uuid,
                    'invoice_sl'       => $i,
                    'total'    => $faker->randomFloat(5, 10.00, 30.00),
                    'sub_total'         => $faker->randomFloat(5, 10.00, 30.00),
                    'created'        => date("Y-m-d H:i:s"),
                    'modified'        => date("Y-m-d H:i:s"),
                    'order_id' => $order,
                    'client_id'         => $this->fetchRow("select user_id from scfl_orders where id = $order")['user_id'],
                    'status'          => $faker->shuffleArray([
                        'paid', 'unpaid', 'cancelled'
                    ])[0]
                ];

                $i++;
            }
        }

        $this->table('scfl_invoices')->insert($orders)->save();
    }
}
