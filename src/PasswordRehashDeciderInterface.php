<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

interface PasswordRehashDeciderInterface
{
    public function needsRehash(string $hash): bool;
}
