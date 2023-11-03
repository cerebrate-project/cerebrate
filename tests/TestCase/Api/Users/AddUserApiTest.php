<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\IndividualsFixture;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Fixture\RolesFixture;
use App\Test\Helper\ApiTestTrait;

class AddUserApiTest extends TestCase
{
    use ApiTestTrait;

    protected const ENDPOINT = '/users/add';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testAddUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->post(
            self::ENDPOINT,
            [
                'individual_id' => IndividualsFixture::INDIVIDUAL_B_ID,
                'organisation_id' => OrganisationsFixture::ORGANISATION_A_ID,
                'role_id' => RolesFixture::ROLE_REGULAR_USER_ID,
                'disabled' => false,
                'username' => 'test123',
                'password' => 'Password123456!',
            ]
        );

        print_r($this->getJsonResponseAsArray());

        $this->assertResponseOk();
        $this->assertResponseContains('"username": "test123"');
        $this->assertDbRecordExists('Users', ['username' => 'test123']);
    }

    public function testAddUserNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $this->post(
            self::ENDPOINT,
            [
                'individual_id' => IndividualsFixture::INDIVIDUAL_B_ID,
                'organisation_id' => OrganisationsFixture::ORGANISATION_A_ID,
                'role_id' => RolesFixture::ROLE_REGULAR_USER_ID,
                'disabled' => false,
                'username' => 'test',
                'password' => 'Password123456!'
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists('Users', ['username' => 'test123']);
    }
}
