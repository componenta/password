<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

interface PasswordHasherInterface
{
    /**
     * Hashes a plain-text password.
     */
    public function hash(string $password): string;
}
