<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class AuthKeysFixture extends TestFixture
{
    public $connection = 'test';

    public const ADMIN_API_KEY = 'd033e22ae348aeb5660fc2140aec35850c4da997';
    public const SYNC_API_KEY = '6b387ced110858dcbcda36edb044dc18f91a0894';
    public const ORG_ADMIN_API_KEY = '1c4685d281d478dbcebd494158024bc3539004d0';
    public const USER_API_KEY = '12dea96fec20593566ab75692c9949596833adc9';

    public function init(): void
    {
        $hasher = new DefaultPasswordHasher();
        $faker = \Faker\Factory::create();

        $this->records = [
            [
                'uuid' => $faker->uuid(),
                'authkey' => $hasher->hash(self::ADMIN_API_KEY),
                'authkey_start' => substr(self::ADMIN_API_KEY, 0, 4),
                'authkey_end' => substr(self::ADMIN_API_KEY, -4),
                'expiration' => 0,
                'user_id' => UsersFixture::USER_ADMIN_ID,
                'comment' => '',
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
            [
                'uuid' => $faker->uuid(),
                'authkey' => $hasher->hash(self::SYNC_API_KEY),
                'authkey_start' => substr(self::SYNC_API_KEY, 0, 4),
                'authkey_end' => substr(self::SYNC_API_KEY, -4),
                'expiration' => 0,
                'user_id' => UsersFixture::USER_SYNC_ID,
                'comment' => '',
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
            [
                'uuid' => $faker->uuid(),
                'authkey' => $hasher->hash(self::ORG_ADMIN_API_KEY),
                'authkey_start' => substr(self::ORG_ADMIN_API_KEY, 0, 4),
                'authkey_end' => substr(self::ORG_ADMIN_API_KEY, -4),
                'expiration' => 0,
                'user_id' => UsersFixture::USER_ORG_ADMIN_ID,
                'comment' => '',
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
            [
                'uuid' => $faker->uuid(),
                'authkey' => $hasher->hash(self::USER_API_KEY),
                'authkey_start' => substr(self::USER_API_KEY, 0, 4),
                'authkey_end' => substr(self::USER_API_KEY, -4),
                'expiration' => 0,
                'user_id' => UsersFixture::USER_REGULAR_USER_ID,
                'comment' => '',
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ]
        ];
        parent::init();
    }
}
