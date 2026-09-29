<?php

declare(strict_types=1);

namespace App\Exception;

use DomainException;

final class WorkspaceProvisionException extends DomainException
{
    /**
     * @param array<array-key, mixed> $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Workspace creation failed.');
    }
}
