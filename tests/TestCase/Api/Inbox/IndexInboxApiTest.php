<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Inbox;

use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\InboxFixture;
use App\Test\Helper\ApiTestTrait;

class IndexInboxApiTest extends TestCase
{
    use ApiTestTrait;

    protected const ENDPOINT = '/inbox/index';

    protected $fixtures = [
        'app.Inbox',
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testIndexInbox(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->get(self::ENDPOINT);

        $this->assertResponseOk();
        $this->assertResponseContains(sprintf('"uuid": "%s"', InboxFixture::INBOX_USER_REGISTRATION_UUID));
        $this->assertResponseContains(sprintf('"uuid": "%s"', InboxFixture::INBOX_INCOMING_CONNECTION_REQUEST_UUID));
    }
}
