<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\EncryptionKeysFixture;
use App\Test\Helper\ApiTestTrait;

class EditEncryptionKeyApiTest extends TestCase
{
    use IntegrationTestTrait;
    use ApiTestTrait;

    protected const ENDPOINT = '/api/v1/encryptionKeys/edit';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
        'app.EncryptionKeys'
    ];

    public function testRevokeEncryptionKey(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, EncryptionKeysFixture::ENCRYPTION_KEY_ORG_A_ID);
        $this->put(
            $url,
            [
                'revoked' => true,
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists(
            'EncryptionKeys',
            [
                'id' => EncryptionKeysFixture::ENCRYPTION_KEY_ORG_A_ID,
                'revoked' => true,
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }

    public function testRevokeAdminEncryptionKeyNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);

        $url = sprintf('%s/%d', self::ENDPOINT, EncryptionKeysFixture::ENCRYPTION_KEY_ORG_B_ID);
        $this->put(
            $url,
            [
                'revoked' => true
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists(
            'EncryptionKeys',
            [
                'id' => EncryptionKeysFixture::ENCRYPTION_KEY_ORG_B_ID,
                'revoked' => true
            ]
        );
        //TODO: $this->assertRequestMatchesOpenApiSpec();
        $this->assertResponseMatchesOpenApiSpec($url, 'put');
    }
}
