<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Helper\ApiTestTrait;

class EditOrganisationApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/organisations/edit';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testEditOrganisation(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, OrganisationsFixture::ORGANISATION_A_ID);
        $this->put(
            $url,
            [
                'name' => 'Test Organisation 4321',
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists(
            'Organisations',
            [
                'id' => OrganisationsFixture::ORGANISATION_A_ID,
                'name' => 'Test Organisation 4321',
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }

    public function testEditOrganisationNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, OrganisationsFixture::ORGANISATION_B_ID);
        $this->put(
            $url,
            [
                'name' => 'Test Organisation 1234'
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists(
            'Organisations',
            [
                'id' => OrganisationsFixture::ORGANISATION_B_ID,
                'name' => 'Test Organisation 1234'
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }
}
