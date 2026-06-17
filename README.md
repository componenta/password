# Componenta Password

Password hashing and password verification contracts backed by PHP's native password API.

Use this package when application code should depend on small password abstractions instead of calling `password_hash()` and `password_verify()` directly.

## Installation

```bash
composer require componenta/password
```

## Usage

```php
use Componenta\Stdlib\PasswordHasher;

$hasher = new PasswordHasher(options: ['cost' => 12]);

$hash = $hasher->hash('secret');

$hasher->verify('secret', $hash); // true
$hasher->needsRehash($hash);      // false when algorithm/options still match
```

`PasswordHasher` exposes its configuration as read-only public state:

```php
$hasher->algorithm; // PASSWORD_BCRYPT by default
$hasher->options;   // options passed to password_hash()

$argon = $hasher->withAlgorithm(PASSWORD_ARGON2ID);
$stronger = $hasher->withOptions(['cost' => 13]);
```

`withAlgorithm()` and `withOptions()` return new immutable instances.

## Contracts

`PasswordHasherInterface` is intentionally limited to creating hashes:

```php
interface PasswordHasherInterface
{
    public function hash(string $password): string;
}
```

`PasswordVerifierInterface` is intentionally limited to checking hashes:

```php
interface PasswordVerifierInterface
{
    public function verify(string $password, string $hash): bool;
}
```

Depend on only the operation a service actually needs. A password reset handler usually needs `PasswordHasherInterface`; an identity confirmation policy usually needs `PasswordVerifierInterface`.

The default `PasswordHasher` implements both interfaces and additionally exposes `needsRehash(string $hash): bool` so applications can migrate stored hashes when algorithm options change.

## Configuration

The constructor accepts:

- `algorithm`: passed to PHP's password API.
- `options`: passed to `password_hash()` and `password_needs_rehash()`.

Keep policy decisions such as minimum password length and breach checks in validation/application code. This package only hashes and verifies already accepted password strings.

## Related Packages

| Package | Why it matters here |
|---|---|
| `componenta/auth` | Uses the password contracts in password-login and reset flows. |
| `componenta/validation` | Validates password length and complexity before hashing. |
| `componenta/di` | Can bind `PasswordHasherInterface` and `PasswordVerifierInterface` in the application container. |
