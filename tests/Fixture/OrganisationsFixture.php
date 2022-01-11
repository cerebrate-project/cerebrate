<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class OrganisationsFixture extends TestFixture
{
    public $connection = 'test';

    public const ORGANISATION_A_ID = 1;
    public const ORGANISATION_B_ID = 2;

    public function init(): void
    {
        $faker = \Faker\Factory::create();

        $this->records = [
            [
                'id' => self::ORGANISATION_A_ID,
                'uuid' => $faker->uuid(),
                'name' => 'Organisation A',
                'url' => $faker->url,
                'nationality' => $faker->countryCode,
                'sector' => 'IT',
                'type' => '',
                'contacts' => '',
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
            [
                'id' => self::ORGANISATION_B_ID,
                'uuid' => $faker->uuid(),
                'name' => 'Organisation B',
                'url' => $faker->url,
                'nationality' => $faker->countryCode,
                'sector' => 'IT',
                'type' => '',
                'contacts' => '',
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ]
        ];
        parent::init();
    }
}
