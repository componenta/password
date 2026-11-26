<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

interface PasswordHasherInterface
{
    /**
     * Hashes a plain-text password.
     */
    public function hash(#[\SensitiveParameter] string $password): string;
}
