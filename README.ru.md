# Componenta Password

Контракты хэширования и проверки паролей на базе нативного password API PHP.

Пакет нужен, когда код приложения должен зависеть от небольших абстракций для паролей, а не вызывать `password_hash()` и `password_verify()` напрямую.

## Установка

```bash
composer require componenta/password
```

## Использование

```php
use Componenta\Stdlib\PasswordHasher;

$hasher = new PasswordHasher(options: ['cost' => 12]);

$hash = $hasher->hash('secret');

$hasher->verify('secret', $hash); // true
$hasher->needsRehash($hash);      // false, пока algorithm/options совпадают
```

`PasswordHasher` открывает конфигурацию для чтения:

```php
$hasher->algorithm; // PASSWORD_BCRYPT по умолчанию
$hasher->options;   // options для password_hash()

$argon = $hasher->withAlgorithm(PASSWORD_ARGON2ID);
$stronger = $hasher->withOptions(['cost' => 13]);
```

`withAlgorithm()` и `withOptions()` возвращают новые immutable-экземпляры.

## Контракты

`PasswordHasherInterface` отвечает только за создание хэша:

```php
interface PasswordHasherInterface
{
    public function hash(string $password): string;
}
```

`PasswordVerifierInterface` отвечает только за проверку хэша:

```php
interface PasswordVerifierInterface
{
    public function verify(string $password, string $hash): bool;
}
```

Зависимость должна указывать только ту операцию, которая реально нужна сервису. Handler сброса пароля обычно зависит от `PasswordHasherInterface`; policy подтверждения текущего пароля обычно зависит от `PasswordVerifierInterface`.

Стандартный `PasswordHasher` реализует оба интерфейса и дополнительно предоставляет `needsRehash(string $hash): bool`, чтобы приложение могло мигрировать сохранённые хэши при смене алгоритма или опций.

## Конфигурация

Конструктор принимает:

- `algorithm`: передаётся в password API PHP.
- `options`: передаются в `password_hash()` и `password_needs_rehash()`.

Решения вроде минимальной длины пароля, сложности и проверки утечек должны оставаться в validation/application-коде. Этот пакет только хэширует и проверяет уже принятые строки паролей.

## Связанные пакеты

| Пакет | Зачем нужен здесь |
|---|---|
| `componenta/auth` | Использует password-контракты в login/reset flow. |
| `componenta/validation` | Проверяет пароль до хэширования. |
| `componenta/di` | Может зарегистрировать `PasswordHasherInterface` и `PasswordVerifierInterface` в контейнере приложения. |
