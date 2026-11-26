<?php

declare(strict_types=1);

namespace Componenta\Stdlib\Tests;

use Componenta\Stdlib\PasswordHasher;

it('hashes and verifies passwords through the native password API', function (): void {
    $hasher = new PasswordHasher(options: ['cost' => 4]);
    $hash = $hasher->hash('secret');

    expect($hasher->verify('secret', $hash))->toBeTrue()
        ->and($hasher->verify('wrong', $hash))->toBeFalse();
});

it('reports when a hash needs to be rehashed with stronger options', function (): void {
    $weak = new PasswordHasher(options: ['cost' => 4]);
    $strong = new PasswordHasher(options: ['cost' => 5]);

    expect($strong->needsRehash($weak->hash('secret')))->toBeTrue();
});

it('exposes hashing configuration and returns changed copies', function (): void {
    $hasher = new PasswordHasher(PASSWORD_BCRYPT, ['cost' => 4]);

    $argon = in_array('argon2id', password_algos(), true) ? $hasher->withAlgorithm('argon2id') : null;
    $strong = $hasher->withOptions(['cost' => 5]);

    expect($hasher->algorithm)->toBe(PASSWORD_BCRYPT)
        ->and($hasher->options)->toBe(['cost' => 4])
        ->and($strong)->not->toBe($hasher)
        ->and($strong->algorithm)->toBe(PASSWORD_BCRYPT)
        ->and($strong->options)->toBe(['cost' => 5]);

    if ($argon !== null) {
        expect($argon->algorithm)->toBe('argon2id')->and($argon->options)->toBe([]);
    }
});


it('rejects passwords that bcrypt would silently truncate', function (): void {
    $hasher = new PasswordHasher(options: ['cost' => 4]);
    $prefix = str_repeat('a', 72);
    $hash = $hasher->hash($prefix);
    expect(fn () => $hasher->hash($prefix . 'b'))->toThrow(\InvalidArgumentException::class)
        ->and($hasher->verify($prefix . 'b', $hash))->toBeFalse()
        ->and($hasher->verify($prefix, $hash))->toBeTrue()
        ->and($hasher->verify($prefix . "\0", $hash))->toBeFalse();
});

it('rejects invalid hashing configuration at construction', function (): void {
    expect(fn () => new PasswordHasher('unknown'))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new PasswordHasher(options: ['cost' => 3]))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new PasswordHasher(options: ['memory_cost' => 8192]))->toThrow(\InvalidArgumentException::class);
});

it('does not retain references to mutable caller configuration', function (): void {
    $cost = 4;
    $options = ['cost' => &$cost];
    $hasher = new PasswordHasher(options: $options);
    $cost = 5;
    expect($hasher->options)->toBe(['cost' => 4])
        ->and(password_get_info($hasher->hash('secret'))['options']['cost'])->toBe(4);
});

it('validates Argon2 parameters against effective default options', function (): void {
    if (!in_array('argon2id', password_algos(), true)) {
        $this->markTestSkipped('Argon2id is unavailable.');
    }

    expect(fn () => new PasswordHasher('argon2id', ['memory_cost' => 1]))
        ->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new PasswordHasher('argon2id', ['threads' => intdiv(PASSWORD_ARGON2_DEFAULT_MEMORY_COST, 8) + 1]))
        ->toThrow(\InvalidArgumentException::class);
});
