<?php


use Phinx\Seed\AbstractSeed;

class OrdersSeeder extends AbstractSeed
{
    public function getUsers()
    {
        return array_map(function ($c) {
            return intval($c['id']);
        }, $this->fetchAll("select id from scfl_users"));
    }

    public function getCompanies()
    {
        return array_map(function ($c) {
            return intval($c['id']);
        }, $this->fetchAll("select id from scfl_companies"));
    }

    public function run()
    {
        $faker  = Faker\Factory::create();
        $orders = [];
        $i      = 12323;

        foreach ($this->getCompanies() as $company) {
            $orderExists = $this->fetchRow("select user_id from scfl_orders where related_with = 'company' and related_with_id = $company");
            if (empty($orderExists)) {
                $orders[] = [
                    'uuid'            => $faker->uuid,
                    'company_id'      => $company,
                    'orders_sl'       => $i,
                    'type'            => null,
                    'total_amount'    => $faker->randomFloat(5),
                    'created'         => date("Y-m-d H:i:s"),
                    'modified'        => date("Y-m-d H:i:s"),
                    'user_id'         => $this->fetchRow("select user_id from scfl_companies where id = $company")['user_id'],
                    'related_with'    => 'company',
                    'related_with_id' => $company,
                    'status'          => $faker->shuffleArray([
                        0,1,2,34
                    ])[0]
                ];

                $i++;
            }
        }

        $this->table('scfl_orders')->insert($orders)->save();
    }
}
