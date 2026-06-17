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

    $argon = $hasher->withAlgorithm(PASSWORD_ARGON2ID);
    $strong = $hasher->withOptions(['cost' => 5]);

    expect($hasher->algorithm)->toBe(PASSWORD_BCRYPT)
        ->and($hasher->options)->toBe(['cost' => 4])
        ->and($argon)->not->toBe($hasher)
        ->and($argon->algorithm)->toBe(PASSWORD_ARGON2ID)
        ->and($argon->options)->toBe(['cost' => 4])
        ->and($strong)->not->toBe($hasher)
        ->and($strong->algorithm)->toBe(PASSWORD_BCRYPT)
        ->and($strong->options)->toBe(['cost' => 5]);
});
