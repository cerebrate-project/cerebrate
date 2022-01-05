<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class UsersFixture extends TestFixture
{
    public $connection = 'test';

    public const ADMIN_USER = 'admin';
    public const ADMIN_PASSWORD = 'Password1234';

    public function init(): void
    {
        $hasher = new DefaultPasswordHasher();


        $this->records = [
            [
                'id' => 1,
                'uuid' => '3ebfbe50-e7d2-406e-a092-f031e604b6e5',
                'username' => self::ADMIN_USER,
                'password' => $hasher->hash(self::ADMIN_PASSWORD),
                'role_id' => 1,
                'individual_id' => 1,
                'disabled' => 0,
                'organisation_id' => 1,
                'created' => '2022-01-04 10:00:00',
                'modified' => '2022-01-04 10:00:00'
            ]
        ];
        parent::init();
    }
}
