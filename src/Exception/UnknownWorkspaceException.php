<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class UnknownWorkspaceException extends RuntimeException
{
    public function __construct(public readonly string $slug)
    {
        parent::__construct(sprintf('Unknown workspace: "%s".', $slug));
    }
}
