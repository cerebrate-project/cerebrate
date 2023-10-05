<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class MetaTemplateDirectoryFixture extends TestFixture
{
    public $connection = 'test';

    public const META_TEMPLATE_DIRECTORY_ID = 1;
    public const META_TEMPLATE_DIRECTORY_UUID = '99c7b7c0-23e2-4ba8-9ad4-97bcdadf4c81';

    public function init(): void
    {
        $this->records = [
            [
                'id' => self::META_TEMPLATE_DIRECTORY_ID,
                'uuid' => self::META_TEMPLATE_DIRECTORY_UUID,
                'name' => 'Test Meta Template Directory',
                'namespace' => 'cerebrate',
                'version' => '1'
            ]
        ];

        parent::init();
    }
}
