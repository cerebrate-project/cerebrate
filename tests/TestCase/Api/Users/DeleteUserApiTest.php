<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Fixture\RolesFixture;
use App\Test\Helper\ApiTestTrait;

class DeleteUserApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/users/delete';

    protected $fixtures = [
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

    public function testDeleteUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, UsersFixture::USER_REGULAR_USER_ID);
        $this->delete($url);

        $this->assertResponseOk();
        $this->assertDbRecordNotExists('Users', ['id' => UsersFixture::USER_REGULAR_USER_ID]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'delete');
        $this->addWarning('TODO: CRUDComponent::delete() sets some view variables, does not take into account `isRest()`, fix it.');
    }

    public function testDeleteUserNotAllowedToRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, UsersFixture::USER_ORG_ADMIN_ID);
        $this->delete($url);

        $this->assertResponseCode(405);
        $this->assertDbRecordExists('Users', ['id' => UsersFixture::USER_ORG_ADMIN_ID]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'delete');
        $this->addWarning('TODO: CRUDComponent::delete() sets some view variables, does not take into account `isRest()`, fix it.');
    }
}
