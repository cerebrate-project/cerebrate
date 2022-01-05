<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class AuthKeysFixture extends TestFixture
{
    public $connection = 'test';

    public const ADMIN_API_KEY = '4cd687b314a3b9c4d83264e6195b9a3706ef4c2f';

    public function init(): void
    {
        $hasher = new DefaultPasswordHasher();

        $this->records = [
            [
                'id' => 1,
                'uuid' => '3ebfbe50-e7d2-406e-a092-f031e604b6e5',
                'authkey' => $hasher->hash(self::ADMIN_API_KEY),
                'authkey_start' => '4cd6',
                'authkey_end' => '4c2f',
                'expiration' => 0,
                'user_id' => 1,
                'comment' => '',
                'created' => time(),
                'modified' => time()
            ]
        ];
        parent::init();
    }
}
