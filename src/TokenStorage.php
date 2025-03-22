<?php

declare(strict_types=1);

namespace BeastBytes\Token\Php;

use BeastBytes\Token\CreateTokenTrait;
use BeastBytes\Token\Token;
use BeastBytes\Token\TokenStorageInterface;
use ReflectionException;
use RuntimeException;

/**
 * Stores Tokens in the specified PHP file
 *
 * @psalm-type RawToken = array{
 *      token: string,
 *      type: string,
 *      user_id: string,
 *      valid_until: int
 *  }
 */
final class TokenStorage implements TokenStorageInterface
{
    use CreateTokenTrait;

    private array $tokens = [];

    /**
     * @param string $filePath File path of the PHP file that contains the tokens.
     * Make sure this file is writable by the Web server process.
     * @throws ReflectionException
     */
    public function __construct(private readonly string $filePath)
    {
        $this->load();
    }

    public function add(Token $token): bool
    {
        if ($this->exists($token->getToken())) {
            return false;
        }

        $this->tokens[$token->gettoken()] = $token;
        $result = $this->save();
        
        return is_int($result) && $result > 0;
    }

    public function clear(): void
    {
        $this->tokens = [];
        $this->save();
    }

    public function delete(Token $token): bool
    {
        unset($this->tokens[$token->getToken()]);
        $result = $this->save();

        return is_int($result);
    }

    public function exists(string $token): bool
    {
        return array_key_exists($token, $this->tokens);
    }

    public function get(string $token): ?Token
    {
        return $this->exists($token) ? $this->tokens[$token] : null;
    }

    /**
     * Loads the tokens from a PHP script file.
     * @throws ReflectionException
     */
    private function load(): void
    {
        /** @psalm-var RawToken[] $tokens */
        $tokens = is_file($this->filePath) ? require $this->filePath : [];

        if (empty($tokens)) {
            $this->clear();
        }

        foreach ($tokens as $token) {
            $this->tokens[$token['token']] = $this
                ->createToken($token)
            ;
        }
    }

    /**
     * Saves tokens to a PHP script file.
     * @see load()
     */
    private function save(): bool|int
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            set_error_handler(static function (int $errorNumber, string $errorString) use ($directory): void {
                if (!is_dir($directory)) {
                    throw new RuntimeException(
                        sprintf('Failed to create directory "%s". ', $directory) . $errorString,
                        $errorNumber,
                    );
                }
            });
            mkdir($directory, permissions: 0775);
            restore_error_handler();
        }

        $format = <<<PHP
"<?php\n\nreturn [\n%s\n];"
PHP;
        $content = '';
        foreach ($this->tokens as $token) {
            $content .= "    [\n";
            foreach ($token->toArray() as $attribute => $value) {
                $content .= sprintf(
                    "        '%s' => %s,\n",
                    $attribute,
                    $value
                );
            }
            $content .= "    ],\n";
        }

        $result = file_put_contents($this->filePath, sprintf($format, $content), LOCK_EX);
        $this->invalidateScriptCache();
        return $result;
    }

    /**
     * Invalidates precompiled script cache (such as OPCache) for the given file.
     */
    private function invalidateScriptCache(): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->filePath, force: true);
        }
    }
}