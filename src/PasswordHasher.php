<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

/**
 * Password hasher using PHP's native password hashing functions.
 *
 * Uses bcrypt by default with configurable cost factor.
 */
final readonly class PasswordHasher implements PasswordHasherInterface, PasswordVerifierInterface
{
    /**
     * @param string $algorithm PASSWORD_BCRYPT, PASSWORD_ARGON2I, PASSWORD_ARGON2ID
     * @param array<string, mixed> $options Algorithm-specific options
     */
    public function __construct(
        private(set) string $algorithm = PASSWORD_BCRYPT,
        private(set) array $options = [],
    ) {}

    #[\Override]
    public function hash(string $password): string
    {
        return password_hash($password, $this->algorithm, $this->options);
    }

    #[\Override]
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Checks if the hash needs to be rehashed.
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }

    public function withAlgorithm(string $algorithm): self
    {
        return new self($algorithm, $this->options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): self
    {
        return new self($this->algorithm, $options);
    }
}
