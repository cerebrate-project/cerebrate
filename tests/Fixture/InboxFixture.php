<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InboxFixture extends TestFixture
{
    public $connection = 'test';
    public $table = 'inbox';

    public const INBOX_USER_REGISTRATION_UUID = 'e783b13a-7019-48f5-848e-582bb930a833';
    public const INBOX_INCOMING_CONNECTION_REQUEST_UUID = '9810bd94-16f9-42e0-b364-af59dba50a34';

    public function init(): void
    {
        $faker = \Faker\Factory::create();

        $this->records = [
            [
                'uuid' => self::INBOX_USER_REGISTRATION_UUID,
                'scope' => 'User',
                'action' => 'Registration',
                'title' => 'User account creation requested for foo@bar.com',
                'origin' => '::1',
                'comment' => null,
                'description' => 'Handle user account for this cerebrate instance',
                'user_id' => UsersFixture::USER_ADMIN_ID,
                'data' => [
                    'email' => 'foo@bar.com',
                    'password' => '$2y$10$dr5C0MWgBx1723yyws0HPudTqHz4k8wJ1PQ1ApVkNuH64LuZAr\/ve',
                ],
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
            [
                'uuid' => self::INBOX_INCOMING_CONNECTION_REQUEST_UUID,
                'scope' => 'LocalTool',
                'action' => 'IncomingConnectionRequest',
                'title' => 'Request for MISP Inter-connection',
                'origin' => 'http://127.0.0.1',
                'comment' => null,
                'description' => 'Handle Phase I of inter-connection when another cerebrate instance performs the request.',
                'user_id' => UsersFixture::USER_ADMIN_ID,
                'data' => [
                    'connectorName' => 'MispConnector',
                    'cerebrateURL' => 'http://127.0.0.1',
                    'local_tool_id' => 1,
                    'remote_tool_id' => 1,
                ],
                'created' => $faker->dateTime()->getTimestamp(),
                'modified' => $faker->dateTime()->getTimestamp()
            ],
        ];
        parent::init();
    }
}
