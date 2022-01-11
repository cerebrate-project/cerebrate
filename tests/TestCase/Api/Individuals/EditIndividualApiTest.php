<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\IndividualsFixture;
use App\Test\Helper\ApiTestTrait;

class EditIndividualApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/individuals/edit';

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

    public function testEditIndividualAsAdmin(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, IndividualsFixture::INDIVIDUAL_REGULAR_USER_ID);
        $this->put(
            $url,
            [
                'email' => 'foo@bar.com',
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists('Individuals', [
            'id' => IndividualsFixture::INDIVIDUAL_REGULAR_USER_ID,
            'email' => 'foo@bar.com'
        ]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }

    public function testEditAnyIndividualNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, IndividualsFixture::INDIVIDUAL_ADMIN_ID);
        $this->put(
            $url,
            [
                'email' => 'foo@bar.com',
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists('Individuals', [
            'id' => IndividualsFixture::INDIVIDUAL_ADMIN_ID,
            'email' => 'foo@bar.com'
        ]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }
}
