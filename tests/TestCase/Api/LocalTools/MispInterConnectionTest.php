<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Users;

use Cake\TestSuite\TestCase;
use App\Test\Fixture\OrganisationsFixture;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\Helper\ApiTestTrait;
use App\Test\Helper\WireMockTestTrait;
use \WireMock\Client\WireMock;

class MispInterConnectionTest extends TestCase
{
    use ApiTestTrait;
    use WireMockTestTrait;

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys',
        'app.Broods',
        'app.LocalTools',
        'app.RemoteToolConnections'
    ];

    private const LOCAL_CEREBRATE_URL = 'http://127.0.0.1';
    private const LOCAL_MISP_INSTANCE_URL = 'http://localhost:8080/MISP_LOCAL';
    private const LOCAL_MISP_SYNC_USER_ID = 999;
    private const LOCAL_MISP_SYNC_USER_AUTHKEY = '7f59533a2f792b389f18b086d88f6d7af02cba3e';
    private const LOCAL_MISP_SYNC_USER_EMAIL = 'sync@misp.local';
    private const LOCAL_MISP_ADMIN_USER_AUTHKEY = 'b17ce79ac0f05916f382ab06ea4790665dbc174c';

    private const REMOTE_CEREBRATE_URL = 'http://127.0.0.1:8080/CEREBRATE_REMOTE';
    private const REMOTE_CEREBRATE_AUTHKEY = 'a192ba3c749b545f9cec6b6bba0643736f6c3022';
    private const REMOTE_MISP_INSTANCE_URL = 'http://localhost:8080/MISP_REMOTE';
    private const REMOTE_MISP_SYNC_USER_ID = 333;
    private const REMOTE_MISP_SYNC_USER_AUTHKEY = '429f629abf98f7bf79e5a7f3a8fc694ca19ed357';
    private const REMOTE_MISP_SYNC_USER_EMAIL = 'sync@misp.remote';

    public function testInterConnectMispViaCerebrate(): void
    {
        $this->skipOpenApiValidations();
        $this->initializeWireMock();
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);

        $faker = \Faker\Factory::create();

        // 1. Create LocalTool connection to `MISP LOCAL` (local MISP instance)
        $this->post(
            sprintf('%s/localTools/add', self::LOCAL_CEREBRATE_URL),
            [
                'name' => 'MISP_LOCAL',
                'connector' => 'MispConnector',
                'settings' => json_encode([
                    'url' => self::LOCAL_MISP_INSTANCE_URL,
                    'authkey' => self::LOCAL_MISP_ADMIN_USER_AUTHKEY,
                    'skip_ssl' => true,
                ]),
                'description' => 'MISP local instance',
                'exposed' => true
            ]
        );
        $this->assertResponseOk();
        $this->assertDbRecordExists('LocalTools', ['name' => 'MISP_LOCAL']);
        $connection = $this->getJsonResponseAsArray();
        // print_r($connection);

        // 2. Create a new Brood (connect to a remote Cerebrate instance)
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $LOCAL_BROOD_UUID = $faker->uuid;

        $this->post(
            '/broods/add',
            [
                'uuid' => $LOCAL_BROOD_UUID,
                'name' => 'Local Brood',
                'url' => self::REMOTE_CEREBRATE_URL,
                'description' => $faker->text,
                'organisation_id' => OrganisationsFixture::ORGANISATION_A_ID,
                'trusted' => true,
                'pull' => true,
                'skip_proxy' => true,
                'authkey' => self::REMOTE_CEREBRATE_AUTHKEY,
            ]
        );
        $this->assertResponseOk();
        $this->assertDbRecordExists('Broods', ['uuid' => $LOCAL_BROOD_UUID]);
        $brood = $this->getJsonResponseAsArray();
        // print_r($brood);

        // 3. Get remote Cerebrate exposed tools
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->mockCerebrateGetExposedToolsResponse('CEREBRATE_REMOTE', self::REMOTE_CEREBRATE_AUTHKEY);
        $this->get(sprintf('/localTools/broodTools/%s', $brood['id']));
        $this->assertResponseOk();
        $tools = $this->getJsonResponseAsArray();
        // print_r($tools);

        // 4. Issue a connection request to the remote MISP instance
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->mockCerebrateGetExposedToolsResponse('CEREBRATE_REMOTE', self::REMOTE_CEREBRATE_AUTHKEY);
        $this->mockMispViewOrganisationByUuid('MISP_LOCAL', OrganisationsFixture::ORGANISATION_A_UUID);
        $this->mockMispCreateSyncUser(
            'MISP_LOCAL',
            self::LOCAL_MISP_ADMIN_USER_AUTHKEY,
            self::REMOTE_MISP_SYNC_USER_ID,
            self::REMOTE_MISP_SYNC_USER_EMAIL,
            self::REMOTE_MISP_SYNC_USER_AUTHKEY
        );
        $this->mockCerebrateCreateMispIncommingConnectionRequest(
            'CEREBRATE_REMOTE',
            UsersFixture::USER_ADMIN_ID,
            self::LOCAL_CEREBRATE_URL,
            self::REMOTE_CEREBRATE_AUTHKEY,
            self::LOCAL_MISP_INSTANCE_URL
        );
        $this->post(
            sprintf('/localTools/connectionRequest/%s/%s', $brood['id'], $tools[0]['id']),
            [
                'local_tool_id' => 1
            ]
        );
        $this->assertResponseOk();
        // $connectionRequest = $this->getJsonResponseAsArray();
        // print_r($connectionRequest);

        // 5. Remote Cerebrate accepts the connection request
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY); // TODO: use the Cerebrate admin authkey
        $this->post(
            '/inbox/createEntry/LocalTool/AcceptedRequest',
            [
                'email' => self::REMOTE_MISP_SYNC_USER_EMAIL,
                'authkey' => self::REMOTE_MISP_SYNC_USER_AUTHKEY,
                'url' => self::LOCAL_MISP_INSTANCE_URL,
                'reflected_user_id' => self::REMOTE_MISP_SYNC_USER_ID,
                'connectorName' => 'MispConnector',
                'cerebrateURL' => self::REMOTE_CEREBRATE_URL,
                'local_tool_id' => 1,
                'remote_tool_id' => 1,
                'tool_name' => 'MISP_LOCAL'
            ]
        );
        $this->assertResponseOk();
        $acceptRequest = $this->getJsonResponseAsArray();
        // print_r($acceptRequest);

        // 6. Finalize the connection
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->mockEnableMispSyncUser('MISP_LOCAL', self::LOCAL_MISP_ADMIN_USER_AUTHKEY, self::REMOTE_MISP_SYNC_USER_ID);
        $stub = $this->mockAddMispServer(
            'MISP_LOCAL',
            self::LOCAL_MISP_ADMIN_USER_AUTHKEY,
            [
                'authkey' => self::REMOTE_MISP_SYNC_USER_AUTHKEY,
                'url' => self::LOCAL_MISP_INSTANCE_URL,
                'name' => 'MISP_LOCAL',
                'remote_org_id' => 1
            ]
        );
        $this->post(sprintf('/inbox/process/%s', $acceptRequest['data']['id']));
        // $finalizeConnection = $this->getJsonResponseAsArray();
        // print_r($finalizeConnection);
        $this->assertResponseOk();
        $this->assertResponseContains('"success": true');
    }

    private function mockCerebrateGetExposedToolsResponse(string $instance, string $cerebrateAuthkey): \WireMock\Stubbing\StubMapping
    {
        return $this->getWireMock()->stubFor(
            WireMock::get(WireMock::urlEqualTo("/$instance/localTools/exposedTools"))
                ->withHeader('Authorization', WireMock::equalTo($cerebrateAuthkey))
                ->willReturn(WireMock::aResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody((string)json_encode(
                        [
                            [
                                "id" => 1,
                                "name" => "MISP_REMOTE",
                                "connector" => "MispConnector",
                                "description" => "Remote MISP instance"
                            ]
                        ]
                    )))
        );
    }

    private function mockMispViewOrganisationByUuid(string $instance, string $orgUuid): \WireMock\Stubbing\StubMapping
    {
        return $this->getWireMock()->stubFor(
            WireMock::get(WireMock::urlEqualTo("/$instance/organisations/view/$orgUuid/limit:50"))
                ->willReturn(WireMock::aResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody((string)json_encode(
                        [
                            "Organisation" => [
                                "id" => 1,
                                "name" => "Local Organisation",
                                "uuid" => $orgUuid,
                                "local" => true
                            ]
                        ]
                    )))
        );
    }

    private function mockMispCreateSyncUser(string $instance, string $mispAuthkey, int $userId, string $email, string $authkey): \WireMock\Stubbing\StubMapping
    {
        return $this->getWireMock()->stubFor(
            WireMock::post(WireMock::urlEqualTo("/$instance/admin/users/add"))
                ->withHeader('Authorization', WireMock::equalTo($mispAuthkey))
                ->willReturn(WireMock::aResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody((string)json_encode(
                        [
                            "User" => [
                                "id" => $userId,
                                "authkey" => $authkey,
                                "email" => $email
                            ]
                        ]
                    )))
        );
    }

    private function mockCerebrateCreateMispIncommingConnectionRequest(
        string $instance,
        int $userId,
        string $cerebrateUrl,
        string $cerebrateAuthkey,
        string $mispUrl
    ): \WireMock\Stubbing\StubMapping {
        $faker = \Faker\Factory::create();

        return $this->getWireMock()->stubFor(
            WireMock::post(WireMock::urlEqualTo("/$instance/inbox/createEntry/LocalTool/IncomingConnectionRequest"))
                ->withHeader('Authorization', WireMock::equalTo($cerebrateAuthkey))
                ->willReturn(WireMock::aResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody((string)json_encode(
                        [
                            'data' => [
                                'id' => $faker->randomNumber(),
                                'uuid' => $faker->uuid,
                                'origin' => $cerebrateUrl,
                                'user_id' => $userId,
                                'data' => [
                                    'connectorName' => 'MispConnector',
                                    'cerebrateURL' => $cerebrateUrl,
                                    'url' => $mispUrl,
                                    'tool_connector' => 'MispConnector',
                                    'local_tool_id' => 1,
                                    'remote_tool_id' => 1,
                                ],
                                'title' => 'Request for MISP Inter-connection',
                                'scope' => 'LocalTool',
                                'action' => 'IncomingConnectionRequest',
                                'description' => 'Handle Phase I of inter-connection when another cerebrate instance performs the request.',
                                'local_tool_connector_name' => 'MispConnector',
                                'created' => date('c'),
                                'modified' => date('c')
                            ],
                            'success' => true,
                            'message' => 'LocalTool request for IncomingConnectionRequest created',
                            'errors' => [],
                        ]
                    )))
        );
    }

    private function mockEnableMispSyncUser(string $instance, string $mispAuthkey, int $userId): \WireMock\Stubbing\StubMapping
    {
        return $this->getWireMock()->stubFor(
            WireMock::post(WireMock::urlEqualTo("/$instance/admin/users/edit/$userId"))
                ->withHeader('Authorization', WireMock::equalTo($mispAuthkey))
                ->withRequestBody(WireMock::equalToJson(json_encode(['disabled' => false])))
                ->willReturn(WireMock::aResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody((string)json_encode(
                        [
                            "User" => [
                                "id" => $userId,
                            ]
                        ]
                    )))
        );
    }

    private function mockAddMispServer(string $instance, string $mispAuthkey, array $body): \WireMock\Stubbing\StubMapping
    {
        $faker = \Faker\Factory::create();

        return $this->getWireMock()->stubFor(
            WireMock::post(WireMock::urlEqualTo("/$instance/servers/add"))
                ->withHeader('Authorization', WireMock::equalTo($mispAuthkey))
                ->withRequestBody(WireMock::equalToJson(json_encode($body)))
                ->willReturn(WireMock::aResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody((string)json_encode(
                        [
                            'Server' => [
                                'id' => $faker->randomNumber()
                            ]
                        ]
                    )))
        );
    }
}
