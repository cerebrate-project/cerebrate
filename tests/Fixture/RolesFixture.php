<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RolesFixture extends TestFixture
{
    public $connection = 'test';

    public $records = [
        [
            'id' => 1,
            'uuid' => '3ebfbe50-e7d2-406e-a092-f031e604b6e4',
            'name' => 'admin',
            'is_default' => true,
            'perm_admin' => true,
            'perm_sync' => true,
            'perm_org_admin' => true
        ]
    ];
}
