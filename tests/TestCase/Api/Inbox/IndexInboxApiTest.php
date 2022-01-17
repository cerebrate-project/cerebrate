<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\InboxFixture;
use App\Test\Helper\ApiTestTrait;

class IndexInboxApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/inbox/index';

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
        $this->assertResponseContains(sprintf('"id": %d', InboxFixture::INBOX_USER_REGISTRATION_ID));
        $this->assertResponseContains(sprintf('"id": %d', InboxFixture::INBOX_INCOMING_CONNECTION_REQUEST_ID));
        // TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec(self::ENDPOINT);
    }
}
