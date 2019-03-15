<?php


use Phinx\Seed\AbstractSeed;

class UsersSeeder extends AbstractSeed
{
    public function run()
    {
        $faker = Faker\Factory::create();
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $gender = $faker->shuffleArray(['male', 'female'])[0];
            $data[] = [
                'uuid'      => $faker->uuid,
                'first_name'      => $faker->firstName($gender),
                'last_name' => $faker->lastName,
                'email_address'         => $faker->email,
                'gender'    => $gender,
                'password'     => password_hash(123456, PASSWORD_BCRYPT),
                'company_name'       => $faker->company,
                'website'       => $faker->url,
                'role'       => $faker->shuffleArray(['admin', 'employee', 'client'])[0],
                'city'       => $faker->city,
                'phone'       => null,
                'profile_pic'       => null,
                'pwd_reset_token'       => md5(mt_rand(1, 9999999) . microtime()),
                'address'       => $faker->address,
                'created'       => $faker->dateTimeBetween('-20 days', 'now')->format("Y-m-d H:i:s"),
                'modified'       => date('Y-m-d H:i:s')
            ];
        }

        $this->table('scfl_users')->insert($data)->save();
    }
}
