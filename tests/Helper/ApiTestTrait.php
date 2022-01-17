<?php

declare(strict_types=1);

namespace App\Test\Helper;

use Cake\Http\Exception\NotImplementedException;
use \League\OpenAPIValidation\PSR7\ValidatorBuilder;
use \League\OpenAPIValidation\PSR7\RequestValidator;
use \League\OpenAPIValidation\PSR7\ResponseValidator;
use \League\OpenAPIValidation\PSR7\OperationAddress;

trait ApiTestTrait
{
    /** @var string */
    protected $_authToken = '';

    /** @var ValidatorBuilder */
    private $validator;

    /** @var RequestValidator */
    private $requestValidator;

    /** @var ResponseValidator */
    private $responseValidator;

    public function setUp(): void
    {
        parent::setUp();
        $this->initializeOpenApiValidator($_ENV['OPENAPI_SPEC'] ?? APP . '../webroot/docs/openapi.yaml');
    }

    public function setAuthToken(string $authToken): void
    {
        $this->_authToken = $authToken;

        // somehow this is not set automatically in test environment
        $_SERVER['HTTP_AUTHORIZATION'] = $authToken;

        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $this->_authToken
            ]
        ]);
    }

    public function assertResponseContainsArray(array $expected): void
    {
        $responseArray = json_decode((string)$this->_response->getBody(), true);
        throw new NotImplementedException('TODO: see codeception seeResponseContainsJson()');
    }

    /**
     * Parse the OpenAPI specification and create a validator
     * 
     * @param string $specFile
     * @return void
     */
    public function initializeOpenApiValidator(string $specFile): void
    {
        $this->validator = (new ValidatorBuilder)->fromYamlFile($specFile);
        $this->requestValidator = $this->validator->getRequestValidator();
        $this->responseValidator = $this->validator->getResponseValidator();
    }

    /**
     * Validates the API request against the OpenAPI spec
     * 
     * @param string $path The path to the API endpoint 
     * @param string $method The HTTP method used to call the endpoint 
     * @return void
     */
    public function assertRequestMatchesOpenApiSpec(string $endpoint, string $method = 'get'): void
    {
        // TODO: find a workaround to create a PSR-7 request object for validation
        throw NotImplementedException("Unfortunately cakephp does not save the PSR-7 request object in the test context");
    }

    /**
     * Validates the API response against the OpenAPI spec
     * 
     * @param string $path The path to the API endpoint 
     * @param string $method The HTTP method used to call the endpoint
     * @return void
     */
    public function assertResponseMatchesOpenApiSpec(string $endpoint, string $method = 'get'): void
    {
        $address = new OperationAddress($endpoint, $method);
        $this->responseValidator->validate($address, $this->_response);
    }

    /** 
     * Validates a record exists in the database
     * 
     * @param string $table The table name
     * @param array $conditions The conditions to check
     * @return void
     * @throws \Exception
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     * 
     * @see https://book.cakephp.org/4/en/orm-query-builder.html
     */
    public function assertDbRecordExists(string $table, array $conditions): void
    {
        $record = $this->getTableLocator()->get($table)->find()->where($conditions)->first();
        if (!$record) {
            throw new \PHPUnit\Framework\AssertionFailedError("Record not found in table '$table' with conditions: " . json_encode($conditions));
        }
        $this->assertNotEmpty($record);
    }

    /** 
     * Validates a record do notexists in the database
     * 
     * @param string $table The table name
     * @param array $conditions The conditions to check
     * @return void
     * @throws \Exception
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     * 
     * @see https://book.cakephp.org/4/en/orm-query-builder.html
     */
    public function assertDbRecordNotExists(string $table, array $conditions): void
    {
        $record = $this->getTableLocator()->get($table)->find()->where($conditions)->first();
        if ($record) {
            throw new \PHPUnit\Framework\AssertionFailedError("Record found in table '$table' with conditions: " . json_encode($conditions));
        }
        $this->assertEmpty($record);
    }
}
