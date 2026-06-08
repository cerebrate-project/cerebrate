<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\EncryptionKey;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class EncryptionKeysTableTest extends TestCase
{
    public const FINGERPRINT = 'ACBF1CE4A8789AA33605A538D47CD68399535AE3';

    public function testVerifySingleGpgAcceptsDateTimeExpirationForEncryptionSubkey(): void
    {
        $table = TableRegistry::getTableLocator()->get('EncryptionKeys');
        $table->gpg = $this->buildGpgStub([
            $this->buildSubKeyStub(new \DateTimeImmutable('@' . (time() + 86400)), true),
        ]);

        $result = $table->verifySingleGPG(new EncryptionKey(['encryption_key' => 'dummy-key']));

        $this->assertTrue($result[0]);
        $this->assertArrayNotHasKey(2, $result);
        $this->assertSame(self::FINGERPRINT, $result[4]);
        $this->assertSame(self::FINGERPRINT, $result[5]);
    }

    public function testVerifySingleGpgRejectsExpiredDateTimeEncryptionSubkey(): void
    {
        $table = TableRegistry::getTableLocator()->get('EncryptionKeys');
        $table->gpg = $this->buildGpgStub([
            $this->buildSubKeyStub(new \DateTimeImmutable('@' . (time() - 86400)), true),
        ]);

        $result = $table->verifySingleGPG(new EncryptionKey(['encryption_key' => 'dummy-key']));

        $this->assertFalse($result[0]);
        $this->assertStringContainsString('does not include a valid subkey', $result[2]);
        $this->assertStringContainsString('expired', $result[2]);
    }

    /**
     * @param array $subKeys Subkey stubs to return from the fake key.
     * @return object
     */
    private function buildGpgStub(array $subKeys): object
    {
        return new class ($subKeys) {
            /**
             * @var array
             */
            private $subKeys;

            public function __construct(array $subKeys)
            {
                $this->subKeys = $subKeys;
            }

            public function keyInfo(string $keyData): array
            {
                return [new class ($this->subKeys) {
                    /**
                     * @var array
                     */
                    private $subKeys;

                    public function __construct(array $subKeys)
                    {
                        $this->subKeys = $subKeys;
                    }

                    public function getPrimaryKey(): object
                    {
                        return new class {
                            public function getFingerprint(): string
                            {
                                return EncryptionKeysTableTest::FINGERPRINT;
                            }
                        };
                    }

                    public function getSubKeys(): array
                    {
                        return $this->subKeys;
                    }
                }];
            }
        };
    }

    /**
     * @param \DateTimeInterface|int|string|null $expiration Expiration value to return.
     * @param bool $canEncrypt Whether the subkey can encrypt.
     * @return object
     */
    private function buildSubKeyStub($expiration, bool $canEncrypt): object
    {
        return new class ($expiration, $canEncrypt) {
            /**
             * @var \DateTimeInterface|int|string|null
             */
            private $expiration;

            /**
             * @var bool
             */
            private $canEncrypt;

            public function __construct($expiration, bool $canEncrypt)
            {
                $this->expiration = $expiration;
                $this->canEncrypt = $canEncrypt;
            }

            public function getExpirationDate()
            {
                return $this->expiration;
            }

            public function canEncrypt(): bool
            {
                return $this->canEncrypt;
            }
        };
    }
}
