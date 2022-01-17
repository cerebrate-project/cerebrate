<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Fixture\BroodsFixture;
use App\Test\Helper\ApiTestTrait;

class EditBroodApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/broods/edit';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
        'app.Broods'
    ];

    public function testEditBrood(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, BroodsFixture::BROOD_A_ID);
        $this->put(
            $url,
            [
                'name' => 'Test Brood 4321',
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists(
            'Broods',
            [
                'id' => BroodsFixture::BROOD_A_ID,
                'name' => 'Test Brood 4321',
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }

    public function testEditBroodNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, BroodsFixture::BROOD_B_ID);
        $this->put(
            $url,
            [
                'name' => 'Test Brood 1234'
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists(
            'Broods',
            [
                'id' => BroodsFixture::BROOD_B_ID,
                'name' => 'Test Brood 1234'
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }
}
