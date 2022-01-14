<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BroodsFixture extends TestFixture
{
    public $connection = 'test';

    public const BROOD_A_ID = 1;
    public const BROOD_A_API_KEY = '6dcd4ce23d88e2ee9568ba546c007c63d9131c1b';

    public const BROOD_B_ID = 2;
    public const BROOD_B_API_KEY = 'ae4f281df5a5d0ff3cad6371f76d5c29b6d953ec';

    public function init(): void
    {
        $faker = \Faker\Factory::create();

        $this->records = [
            [
                'id' => self::BROOD_A_ID,
                'uuid' => $faker->uuid(),
                'name' => 'Brood A',
                'url' => $faker->url,
                'description' => $faker->text,
                'organisation_id' => OrganisationsFixture::ORGANISATION_A_ID,
                'trusted' => true,
                'pull' => true,
                'skip_proxy' => true,
                'authkey' => self::BROOD_A_API_KEY,
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
            [
                'id' => self::BROOD_B_ID,
                'uuid' => $faker->uuid(),
                'name' => 'Brood A',
                'url' => $faker->url,
                'description' => $faker->text,
                'organisation_id' => OrganisationsFixture::ORGANISATION_B_ID,
                'trusted' => true,
                'pull' => true,
                'skip_proxy' => true,
                'authkey' => self::BROOD_B_API_KEY,
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ]
        ];
        parent::init();
    }
}
