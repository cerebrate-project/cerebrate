<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\BroodsFixture;
use App\Test\Helper\ApiTestTrait;

class DeleteAuthKeyApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/authKeys/delete';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
    ];

    public function testDeleteAdminAuthKey(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, AuthKeysFixture::ADMIN_API_ID);
        $this->delete($url);

        $this->assertResponseOk();
        $this->assertDbRecordNotExists('AuthKeys', ['id' => AuthKeysFixture::ADMIN_API_ID]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'delete');
    }

    public function testDeleteOrgAdminAuthKeyNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, AuthKeysFixture::ORG_ADMIN_API_ID);
        $this->delete($url);

        $this->assertResponseCode(405);
        $this->assertDbRecordExists('AuthKeys', ['id' => AuthKeysFixture::ORG_ADMIN_API_ID]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'delete');
    }
}
