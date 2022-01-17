<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Fixture\SharingGroupsFixture;
use App\Test\Helper\ApiTestTrait;

class EditSharingGroupApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/sharingGroups/edit';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
        'app.SharingGroups'
    ];

    public function testEditSharingGroup(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, SharingGroupsFixture::SHARING_GROUP_A_ID);
        $this->put(
            $url,
            [
                'name' => 'Test Sharing Group 4321',
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists(
            'SharingGroups',
            [
                'id' => SharingGroupsFixture::SHARING_GROUP_A_ID,
                'name' => 'Test Sharing Group 4321',
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }

    public function testEditSharingGroupNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, SharingGroupsFixture::SHARING_GROUP_B_ID);
        $this->put(
            $url,
            [
                'name' => 'Test Sharing Group 1234'
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists(
            'SharingGroups',
            [
                'id' => SharingGroupsFixture::SHARING_GROUP_B_ID,
                'name' => 'Test Sharing Group 1234'
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }
}
