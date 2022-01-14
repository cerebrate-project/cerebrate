<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\IndividualsFixture;
use App\Test\Helper\ApiTestTrait;

class ViewIndividualApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/individuals/view';

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

    public function testViewIndividualById(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, IndividualsFixture::INDIVIDUAL_ADMIN_ID);
        $this->get($url);

        $this->assertResponseOk();
        $this->assertResponseContains(sprintf('"id": %d', IndividualsFixture::INDIVIDUAL_ADMIN_ID));
        // TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url);
    }
}
