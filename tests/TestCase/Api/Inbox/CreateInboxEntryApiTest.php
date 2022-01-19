<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Helper\ApiTestTrait;

class CreateInboxEntryApiTest extends TestCase
{
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/inbox/createEntry';

    protected $fixtures = [
        'app.Inbox',
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testAddUserRegistrationInbox(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);

        // to avoid $this->request->clientIp() to return null
        $_SERVER['REMOTE_ADDR'] = '::1';

        $url = sprintf("%s/%s/%s", self::ENDPOINT, 'User', 'Registration');
        $this->post(
            $url,
            [
                'email' => 'john@example.com',
                'password' => 'Password12345!'
            ]
        );

        $this->assertResponseOk();
        $this->assertResponseContains('"email": "john@example.com"');
        $this->assertDbRecordExists(
            'Inbox',
            [
                'id' => 3, // hacky, but `data` is json string cannot verify the value because of the hashed password
                'scope' => 'User',
                'action' => 'Registration',
            ]
        );
    }

    public function testAddUserRegistrationInboxNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);

        $url = sprintf("%s/%s/%s", self::ENDPOINT, 'User', 'Registration');
        $this->post(
            $url,
            [
                'email' => 'john@example.com',
                'password' => 'Password12345!'
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists('Inbox', ['id' => 3]);
    }
}
