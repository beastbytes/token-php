<?php

namespace BeastBytes\Token\Php\Tests;

use BeastBytes\Token\CreateTokenTrait;
use BeastBytes\Token\Php\TokenStorage;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @psalm-type RawToken = array{
 *     token: string,
 *     type: string,
 *     user_id: string,
 *     valid_until: int
 * }
 */
class TokenStorageTest extends TestCase
{
    private const TEST_TOKEN_VALUE = 'test-token-value';
    private const TEST_TOKEN_TYPE = 'test-token-type';

    private $tokenStorage;
    private $testTokenPath;

    use CreateTokenTrait;

    protected function setUp(): void
    {
        // Create a temporary file for testing
        $this->testTokenPath = sys_get_temp_dir() . '/test-tokens.php';
        $this->tokenStorage = new TokenStorage($this->testTokenPath);
    }

    protected function tearDown(): void
    {
        // Clean up test token file if it exists
        if (file_exists($this->testTokenPath)) {
            unlink($this->testTokenPath);
        }
    }

    /**
     * @throws ReflectionException
     */
    #[dataProvider('tokensProvider')]
    public function testAddAndGetToken(array $rawToken): void
    {
        $token = $this->createToken($rawToken);

        $this->assertFalse($this
            ->tokenStorage
            ->exists($rawToken['token'])
        );

        $this->assertTrue($this
            ->tokenStorage
            ->add($token)
        );

        $this->assertTrue($this
            ->tokenStorage
            ->exists($rawToken['token'])
        );

        $retrievedToken = $this
            ->tokenStorage
            ->get($rawToken['token'])
        ;

        $this->assertEquals($token, $retrievedToken);
    }

    /**
     * @throws ReflectionException
     */
    #[dataProvider('tokenProvider')]
    public function testCantAddDuplicateToken(array $rawToken): void
    {
        $token = $this->createToken($rawToken);

        $this->assertFalse($this
            ->tokenStorage
            ->exists($rawToken['token'])
        );

        $this->assertTrue($this
            ->tokenStorage
            ->add($token)
        );

        $this->assertTrue($this
            ->tokenStorage
            ->exists($rawToken['token'])
        );

        $this->assertFalse($this
            ->tokenStorage
            ->add($token)
        );
    }

    public function testGetTokenReturnsNullWhenNoTokenExists(): void
    {
        $retrievedToken = $this
            ->tokenStorage
            ->get(self::TEST_TOKEN_VALUE)
        ;

        $this->assertNull($retrievedToken);
    }

    public function testGetTokenReturnsNullWhenTokenFileIsEmpty(): void
    {
        // Create an empty token file
        file_put_contents($this->testTokenPath, '');

        $retrievedToken = $this->tokenStorage->get(self::TEST_TOKEN_VALUE);

        $this->assertNull($retrievedToken);
    }

    /**
     * @throws ReflectionException
     */
    #[dataProvider('tokensProvider')]
    public function testDeleteToken(array $rawToken): void
    {
        $token = $this->createToken($rawToken);

        $this
            ->tokenStorage
            ->add($token)
        ;

        $this->assertTrue($this
            ->tokenStorage
            ->exists($rawToken['token'])
        );

        $this
            ->tokenStorage
            ->delete($token)
        ;

        $this->assertFalse($this
            ->tokenStorage
            ->exists($rawToken['token'])
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testDeleteTokenWhenNoTokenExists(): void
    {
        $this
            ->tokenStorage
            ->clear()
        ;

        // This should not throw an exception
        $token = $this->createToken([
            'token' => self::TEST_TOKEN_VALUE,
            'type' => self::TEST_TOKEN_TYPE,
            'user_id' => random_int(1, 100),
            'valid_until' => time() + random_int(-100, 100) * 3600
        ]);

        $this->assertTrue($this
            ->tokenStorage
            ->delete($token)
        );
    }

    public function testClearTokens(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $token = $this->createToken([
                'token' => self::TEST_TOKEN_VALUE . '-' . $i,
                'type' => self::TEST_TOKEN_TYPE,
                'user_id' => random_int(1, 100),
                'valid_until' => time() + random_int(-100, 100) * 3600
            ]);

            $this
                ->tokenStorage
                ->add($token)
            ;

            $this->assertTrue($this
                ->tokenStorage
                ->exists($token->getToken())
            );
        }

        $this
            ->tokenStorage
            ->clear()
        ;

        for ($i = 0; $i < 10; $i++) {
            $token = self::TEST_TOKEN_VALUE . '-' . $i;

            $this->assertFalse($this
                ->tokenStorage
                ->exists($token)
            );
        }
    }

    public static function tokenProvider(): Generator
    {
        for ($i = 0; $i < 10; $i++) {
            yield 'test-token-value-' . $i => [[
                'token' => self::TEST_TOKEN_VALUE . '-' . $i,
                'type' => self::TEST_TOKEN_TYPE,
                'user_id' => random_int(1, 100),
                'valid_until' => time() + random_int(-100, 100) * 3600
            ]];
        }
    }
}