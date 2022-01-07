<?php

declare(strict_types=1);

namespace App\Test\Helper;

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

    /**
     * Parse the OpenAPI specification and create a validator
     * 
     * @param string $specFile
     * @return void
     */
    public function initializeValidator(string $specFile): void
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
    public function validateRequest(string $endpoint, string $method = 'get'): void
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
    public function validateResponse(string $endpoint, string $method = 'get'): void
    {
        $address = new OperationAddress($endpoint, $method);
        $this->responseValidator->validate($address, $this->_response);
    }
}
