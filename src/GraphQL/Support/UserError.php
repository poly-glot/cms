<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use GraphQL\Error\Error;

final class UserError extends Error
{
    public static function forbidden(string $message = 'You are not allowed to do that.'): self
    {
        return new self($message, extensions: ['code' => 'FORBIDDEN']);
    }
}
