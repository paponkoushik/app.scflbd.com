<?php


use Phinx\Seed\AbstractSeed;

class CompaniesSeeder extends AbstractSeed
{
    public function getUsers()
    {
        $userData = $this->fetchAll("select id from scfl_users limit 0, 25");

        return array_map(function ($u) {
            return intval($u['id']);
        }, $userData);
    }

    public function run()
    {
        $userIds = $this->getUsers();

        $faker = Faker\Factory::create();
        $data  = [];
        foreach ($userIds as $user_id) {
            for ($i = 0; $i < 5; $i++) {
                $data[] = [
                    'primary_street'            => $faker->streetAddress,
                    'secondary_street'          => $faker->streetAddress,
                    'postcode'                  => 1234,
                    'city'                      => $faker->city,
                    'country'                   => $faker->countryCode,
                    'authorised_capital'        => $faker->randomNumber(5),
                    'paid_up_capital'           => $faker->randomNumber(5),
                    'qualification_of_director' => $faker->realText(128),
                    'board_meeting'             => $faker->realText(10),
                    'agm'                       => $faker->realText(10),
                    'chairman'                  => $faker->realText(10),
                    'managing_director'         => $faker->realText(10),
                    'power_of_management'       => $faker->realText(10),
                    'signing_bank_ac'           => $faker->realText(10),
                    'business_type'             => $faker->shuffleArray([
                        'public_company',
                        'private_company',
                        'society'
                    ])[0],
                    'created'                   => date('Y-m-d H:i:s'),
                    'user_id'                   => $user_id,
                    'company_name_one'          => $faker->company,
                    'company_name_two'          => $faker->company,
                    'company_name_three'        => $faker->company,
                    'company_name_four'         => $faker->company,
                    'uuid'                      => $faker->uuid,
                    'status'                    => $faker->shuffleArray([
                        'pending',
                        'registered',
                        'under_processing',
                        'cancelled',
                        'rejected'
                    ])[0],
                ];
            }
        }

        $this->table('scfl_companies')->insert($data)->save();
    }
}
