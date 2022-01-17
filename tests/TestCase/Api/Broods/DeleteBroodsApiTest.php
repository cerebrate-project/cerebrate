<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\BroodsFixture;
use App\Test\Helper\ApiTestTrait;

class DeleteBroodsApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/broods/delete';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
        'app.Broods'
    ];

    public function testDeleteBrood(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, BroodsFixture::BROOD_A_ID);
        $this->delete($url);

        $this->assertResponseOk();
        $this->assertDbRecordNotExists('Broods', ['id' => BroodsFixture::BROOD_A_ID]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'delete');
        $this->addWarning('TODO: CRUDComponent::delete() sets some view variables, does not take into account `isRest()`, fix it.');
    }

    public function testDeleteBroodNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, BroodsFixture::BROOD_A_ID);
        $this->delete($url);

        $this->assertResponseCode(405);
        $this->assertDbRecordExists('Broods', ['id' => BroodsFixture::BROOD_A_ID]);
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'delete');
        $this->addWarning('TODO: CRUDComponent::delete() sets some view variables, does not take into account `isRest()`, fix it.');
    }
}
