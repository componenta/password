<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

interface PasswordVerifierInterface
{
    /**
     * Verifies a password against a hash.
     */
    public function verify(string $password, string $hash): bool;
}
