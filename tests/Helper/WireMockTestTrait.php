<?php

declare(strict_types=1);

namespace App\Test\Helper;

use \WireMock\Client\WireMock;
use Exception;

trait WireMockTestTrait
{
    /** @var WireMock */
    private $wiremock;

    /** @var array<mixed> */
    private $config = [
        'hostname' => 'localhost',
        'port' => 8080
    ];

    public function initializeWireMock(): void
    {
        $this->wiremock = WireMock::create(
            $_ENV['WIREMOCK_HOST'] ?? $this->config['hostname'],
            $_ENV['WIREMOCK_PORT'] ?? $this->config['port']
        );

        if (!$this->wiremock->isAlive()) {
            throw new Exception('Failed to connect to WireMock server.');
        }

        $this->clearWireMockStubs();
    }

    public function clearWireMockStubs(): void
    {
        $this->wiremock->resetToDefault();
    }

    public function getWireMock(): WireMock
    {
        return $this->wiremock;
    }

    public function getWireMockBaseUrl(): string
    {
        return sprintf('http://%s:%s', $this->config['hostname'], $this->config['port']);
    }
}
