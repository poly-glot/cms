<?php

declare(strict_types=1);

namespace App\Model\Tenancy;

use App\Exception\TenantContextException;
use App\Model\Enum\UserRole;

final class TenantContext
{
    private static ?self $instance = null;

    public private(set) ?int $workspaceId = null;

    public private(set) ?string $workspaceSlug = null;

    public private(set) ?UserRole $role = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function activate(int $workspaceId, ?UserRole $role, ?string $workspaceSlug = null): void
    {
        $this->workspaceId = $workspaceId;
        $this->workspaceSlug = $workspaceSlug;
        $this->role = $role;
    }

    public function requireWorkspaceId(): int
    {
        if ($this->workspaceId === null) {
            throw new TenantContextException('No active workspace for this request.');
        }

        return $this->workspaceId;
    }

    public function clear(): void
    {
        $this->workspaceId = null;
        $this->workspaceSlug = null;
        $this->role = null;
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function runScoped(int $workspaceId, ?UserRole $role, callable $callback, ?string $workspaceSlug = null): mixed
    {
        $previousWorkspaceId = $this->workspaceId;
        $previousSlug = $this->workspaceSlug;
        $previousRole = $this->role;
        $this->activate($workspaceId, $role, $workspaceSlug);

        try {
            return $callback();
        } finally {
            $this->workspaceId = $previousWorkspaceId;
            $this->workspaceSlug = $previousSlug;
            $this->role = $previousRole;
        }
    }
}
