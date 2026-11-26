<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class PasswordHasher implements PasswordHasherInterface, PasswordVerifierInterface, PasswordRehashDeciderInterface
{
    public string $algorithm;
    public array $options;

    public function __construct(
        string $algorithm = PASSWORD_BCRYPT,
        array $options = [],
    ) {
        if (!in_array($algorithm, password_algos(), true)) {
            throw new InvalidArgumentException('Unsupported password hashing algorithm.');
        }

        $allowed = $algorithm === PASSWORD_BCRYPT
            ? ['cost']
            : ['memory_cost', 'time_cost', 'threads'];

        $snapshot = [];
        foreach ($options as $name => $value) {
            if (!in_array($name, $allowed, true) || !is_int($value) || $value < 1) {
                throw new InvalidArgumentException('Invalid password hashing option.');
            }
            $snapshot[$name] = $value;
        }

        if ($algorithm === PASSWORD_BCRYPT && isset($options['cost'])
            && ($options['cost'] < 4 || $options['cost'] > 31)) {
            throw new InvalidArgumentException('Bcrypt cost must be between 4 and 31.');
        }

        if ($algorithm !== PASSWORD_BCRYPT) {
            $memory = $snapshot['memory_cost'] ?? PASSWORD_ARGON2_DEFAULT_MEMORY_COST;
            $time = $snapshot['time_cost'] ?? PASSWORD_ARGON2_DEFAULT_TIME_COST;
            $threads = $snapshot['threads'] ?? PASSWORD_ARGON2_DEFAULT_THREADS;

            if (defined('PASSWORD_ARGON2_PROVIDER') && PASSWORD_ARGON2_PROVIDER === 'sodium'
                && isset($snapshot['threads'])) {
                throw new InvalidArgumentException('The sodium Argon2 provider does not support the threads option.');
            }

            if ($threads > 0xffffff || $time > 0xffffffff || $memory > 0xffffffff
                || $threads > intdiv($memory, 8)) {
                throw new InvalidArgumentException('Argon2 options exceed supported parameter bounds.');
            }
        }

        $this->algorithm = $algorithm;
        $this->options = $snapshot;
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
