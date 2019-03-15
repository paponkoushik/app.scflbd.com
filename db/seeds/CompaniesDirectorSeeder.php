<?php


use Phinx\Seed\AbstractSeed;

class CompaniesDirectorSeeder extends AbstractSeed
{
    public function getCompanies()
    {
        return array_map(function ($c){
            return intval($c['id']);
        }, $this->fetchAll("select id from scfl_companies"));
    }
    public function run()
    {
        $faker = Faker\Factory::create();
        $data  = [];

        foreach ($this->getCompanies() as $company) {
            for ($i = 0; $i < rand(1, 5); $i++) {
                $data[] = [
                    'name' => $faker->name,
                    'father_or_husband_name' => $faker->name,
                    'mother_name' => $faker->name,
                    'present_address' => null,
                    'mobile_nmm' => $faker->phoneNumber,
                    'country' => $faker->countryCode,
                    'email_address' => $faker->email,
                    'date_of_birth' => $faker->dateTimeBetween("-30 years", "-10 years")->format("Y-m-d"),
                    'designation' => $faker->shuffleArray(['CEO', 'Chairman', 'Managing Director', "Share Holder"])[0],
                    'etin_no' => $faker->bankAccountNumber,
                    'shares_quantity' => rand(1, 100),
                    'created' => date("Y-m-d H:i:s"),
                    'nid_password_no' => md5(12345),
                    'uuid' => $faker->uuid,
                    'company_id' => $company
                ];
            }
        }

        $this->table('scfl_companies_directors')->insert($data)->save();
    }
}
