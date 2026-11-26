<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class PasswordHasher implements PasswordHasherInterface, PasswordVerifierInterface, PasswordRehashDeciderInterface
{
    public function __construct(
        private(set) string $algorithm = PASSWORD_BCRYPT,
        private(set) array $options = [],
    ) {
        if (!in_array($algorithm, password_algos(), true)) {
            throw new InvalidArgumentException('Unsupported password hashing algorithm.');
        }

        $allowed = $algorithm === PASSWORD_BCRYPT
            ? ['cost']
            : ['memory_cost', 'time_cost', 'threads'];

        foreach ($options as $name => $value) {
            if (!in_array($name, $allowed, true) || !is_int($value) || $value < 1) {
                throw new InvalidArgumentException('Invalid password hashing option.');
            }
        }

        if ($algorithm === PASSWORD_BCRYPT && isset($options['cost'])
            && ($options['cost'] < 4 || $options['cost'] > 31)) {
            throw new InvalidArgumentException('Bcrypt cost must be between 4 and 31.');
        }

        if ($algorithm !== PASSWORD_BCRYPT && isset($options['memory_cost'])
            && $options['memory_cost'] < 8 * ($options['threads'] ?? 1)) {
            throw new InvalidArgumentException('Argon2 memory cost is too small for the requested threads.');
        }
    }

    #[\Override]
    public function hash(#[SensitiveParameter] string $password): string
    {
        if ($this->algorithm === PASSWORD_BCRYPT && (strlen($password) > 72 || str_contains($password, "\0"))) {
            throw new InvalidArgumentException('Bcrypt passwords must not exceed 72 bytes or contain NUL bytes.');
        }

        return password_hash($password, $this->algorithm, $this->options);
    }

    #[\Override]
    public function verify(#[SensitiveParameter] string $password, string $hash): bool
    {
        // Verification is governed by the stored hash, not the configured new-hash algorithm.
        if (preg_match('/\A\$2[abxy]\$/', $hash) === 1
            && (strlen($password) > 72 || str_contains($password, "\0"))) {
            return false;
        }

        return password_verify($password, $hash);
    }

    #[\Override]
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }

    public function withAlgorithm(string $algorithm): self
    {
        // Options of different algorithms are not interchangeable.
        return new self($algorithm, $algorithm === $this->algorithm ? $this->options : []);
    }

    public function withOptions(array $options): self
    {
        return new self($this->algorithm, $options);
    }
}
