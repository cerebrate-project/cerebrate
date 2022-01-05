<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class IndividualsFixture extends TestFixture
{
    public $connection = 'test';

    public $records = [
        [
            'id' => 1,
            'uuid' => '3ebfbe50-e7d2-406e-a092-f031e604b6e1',
            'email' => 'admin@admin.test',
            'first_name' => 'admin',
            'last_name' => 'admin',
            'position' => 'admin',
            'created' => '2022-01-04 10:00:00',
            'modified' => '2022-01-04 10:00:00'
        ]
    ];
}
