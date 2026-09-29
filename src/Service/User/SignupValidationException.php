<?php

declare(strict_types=1);

namespace App\Service\User;

use DomainException;

final class SignupValidationException extends DomainException
{
    /**
     * @param array<array-key, mixed> $errors
     */
    public function __construct(
        public readonly array $errors,
        public readonly string $scope,
    ) {
        parent::__construct(sprintf('Signup failed at %s stage.', $scope));
    }
}
