<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use App\Model\Enum\TokenScope;
use App\Model\Enum\UserRole;
use Authorization\IdentityInterface;
use GraphQL\Error\DebugFlag;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\ValidationRule;

final readonly class GraphqlContext
{
    private const int MAX_QUERY_DEPTH = 12;

    private const int MAX_QUERY_COMPLEXITY = 500;

    /**
     * @param list<TokenScope> $scopes
     */
    public function __construct(
        public int $workspaceId,
        public string $workspaceSlug,
        public ?UserRole $role,
        public BatchLoader $loaders,
        public ?IdentityInterface $viewer,
        public array $scopes,
        private bool $debug,
    ) {
    }

    public function canPreview(): bool
    {
        return $this->hasScope(TokenScope::Preview);
    }

    public function hasScope(TokenScope $scope): bool
    {
        return self::grantsAtLeast($this->scopes, $scope);
    }

    public function requireWritable(): void
    {
        if ($this->role === null || !$this->hasScope(TokenScope::Write)) {
            throw UserError::forbidden();
        }
    }

    public function requireViewerId(): int
    {
        $identifier = $this->viewer !== null ? ($this->viewer['id'] ?? null) : null;
        if (!is_numeric($identifier)) {
            throw UserError::forbidden('You must be authenticated to do that.');
        }

        return (int) $identifier;
    }

    public function authorize(object $resource, string $action): void
    {
        if ($this->viewer === null || !$this->viewer->can($action, $resource)) {
            throw UserError::forbidden();
        }
    }

    /**
     * @param list<TokenScope> $granted
     */
    public static function grantsAtLeast(array $granted, TokenScope $needed): bool
    {
        return array_any($granted, static fn (TokenScope $scope): bool => $scope->isAtLeast($needed));
    }

    public function debugFlags(): int
    {
        if (!$this->debug) {
            return DebugFlag::NONE;
        }

        return DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
    }

    /**
     * @return list<ValidationRule>
     */
    public function validationRules(): array
    {
        $rules = [
            new QueryComplexity(self::MAX_QUERY_COMPLEXITY),
            new QueryDepth(self::MAX_QUERY_DEPTH),
        ];

        if (!$this->debug) {
            $rules[] = new DisableIntrospection(DisableIntrospection::ENABLED);
        }

        return $rules;
    }
}
