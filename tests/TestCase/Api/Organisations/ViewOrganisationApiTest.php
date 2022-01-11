<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\Helper\ApiTestTrait;

class ViewOrganisationApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/organisations/view';

    protected $fixtures = [
        'app.TagsTags',
        'app.Organisations',
        'app.TagsTaggeds',
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

    public function testViewOrganisationById(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, OrganisationsFixture::ORGANISATION_A_ID);
        $this->get($url);

        $this->assertResponseOk();
        $this->assertResponseContains('"name": "Organisation A"');
        // TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url);
    }
}
