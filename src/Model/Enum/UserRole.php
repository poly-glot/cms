<?php

declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Tenancy\TenantContext;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Author = 'author';
    case Contributor = 'contributor';

    public static function fromValue(string $value): self
    {
        return self::tryFrom($value) ?? self::Author;
    }

    /**
     * The active membership role for the current request, resolved from
     * TenantResolutionMiddleware. Returns Author as a least-privilege default
     * when no workspace/role is active (public zones, jobs, tests without
     * setup).
     */
    public static function current(): self
    {
        return TenantContext::instance()->role ?? self::Author;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /** Editorial roles publish, edit anything, and moderate comments. */
    public function isEditorial(): bool
    {
        return $this === self::Admin || $this === self::Editor;
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Editor => 'Editor',
            self::Author => 'Author',
            self::Contributor => 'Contributor',
        };
    }

    /** The one-line permission summary shown on the Users & Roles screen. */
    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access — manage users, settings, and the whole site.',
            self::Editor => 'Can publish, edit anything, manage comments. Cannot change billing.',
            self::Author => 'Can write and submit drafts. Cannot publish.',
            self::Contributor => 'Can write drafts and reply to comments on their own pages.',
        };
    }
}
