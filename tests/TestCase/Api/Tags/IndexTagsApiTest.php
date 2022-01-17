<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\Helper\ApiTestTrait;

class IndexTagsApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/tags/index';

    protected $fixtures = [
        'app.TagsTags',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testIndexTags(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->get(self::ENDPOINT);


        $this->assertResponseOk();
        $this->assertResponseContains('"name": "red"');
        $this->assertResponseContains('"name": "green"');
        $this->assertResponseContains('"name": "blue"');
        // TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec(self::ENDPOINT);
    }
}
