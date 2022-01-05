<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\UsersFixture;

class UsersApiTest extends TestCase
{
    use IntegrationTestTrait;

    protected $fixtures = [
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testViewMe(): void
    {
        // ugly hack, $_SERVER['HTTP_AUTHORIZATION'] is not set automatically in test environment
        $_SERVER['HTTP_AUTHORIZATION'] = AuthKeysFixture::ADMIN_API_KEY;
        $this->configRequest([
            'headers' => [
                // this does not work: https://book.cakephp.org/4/en/development/testing.html#testing-stateless-authentication-and-apis
                // 'Authorization' => AuthKeysFixture::ADMIN_API_KEY,
                'Accept' => 'application/json'
            ]
        ]);

        $this->get('/users/view');

        $this->assertResponseOk();
        $this->assertResponseContains(sprintf('"username": "%s"', UsersFixture::ADMIN_USER));
    }
}
