<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\Fixture\RolesFixture;
use App\Test\Helper\ApiTestTrait;

class EditUserApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/users/edit';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->initializeValidator(APP . '../webroot/docs/openapi.yaml');
    }

    public function testEditUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, UsersFixture::USER_REGULAR_USER_ID);
        $this->put(
            $url,
            [
                'id' => UsersFixture::USER_REGULAR_USER_ID,
                'role_id' => RolesFixture::ROLE_ORG_ADMIN_ID,
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists('Users', [
            'id' => UsersFixture::USER_REGULAR_USER_ID,
            'role_id' => RolesFixture::ROLE_ORG_ADMIN_ID
        ]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }

    public function testEditRoleNotAllowedToRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $this->put(
            self::ENDPOINT,
            [
                'role_id' => RolesFixture::ROLE_ADMIN_ID,
            ]
        );

        $this->assertDbRecordNotExists('Users', [
            'id' => UsersFixture::USER_REGULAR_USER_ID,
            'role_id' => RolesFixture::ROLE_ADMIN_ID
        ]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec(self::ENDPOINT, 'put');
    }

    public function testEditSelfUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $this->put(
            self::ENDPOINT,
            [
                'username' => 'test',
            ]
        );

        $this->assertDbRecordExists('Users', [
            'id' => UsersFixture::USER_REGULAR_USER_ID,
            'username' => 'test'
        ]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec(self::ENDPOINT, 'put');
    }
}
