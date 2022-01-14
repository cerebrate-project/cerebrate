<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\SharingGroupsFixture;
use App\Test\Helper\ApiTestTrait;

class ViewSharingGroupApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/sharingGroups/view';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
        'app.SharingGroups'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->initializeValidator(APP . '../webroot/docs/openapi.yaml');
    }

    public function testVieSharingGroupById(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, SharingGroupsFixture::SHARING_GROUP_A_ID);
        $this->get($url);

        $this->assertResponseOk();
        $this->assertResponseContains('"name": "Sharing Group A"');
        // TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url);
    }
}
