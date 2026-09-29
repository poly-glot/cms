<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\TestSuite\TestCase;

final class UserRoleTest extends TestCase
{
    public function testLabels(): void
    {
        $this->assertSame('Administrator', UserRole::Admin->label());
        $this->assertSame('Contributor', UserRole::Contributor->label());
    }

    public function testDescriptionsCarryThePermissionSummaries(): void
    {
        $this->assertStringContainsString('Cannot publish', UserRole::Author->description());
        $this->assertStringContainsString('manage comments', UserRole::Editor->description());
    }

    public function testFromValueFallsBackToAuthor(): void
    {
        $this->assertSame(UserRole::Editor, UserRole::fromValue('editor'));
        $this->assertSame(UserRole::Author, UserRole::fromValue('does-not-exist'));
    }

    public function testCurrentReadsActiveMembershipRole(): void
    {
        TenantContext::instance()->activate(1, UserRole::Editor, 'cabinet');

        $this->assertSame(UserRole::Editor, UserRole::current());
    }

    public function testCurrentDefaultsToAuthorWhenNoActiveRole(): void
    {
        TenantContext::instance()->clear();

        $this->assertSame(UserRole::Author, UserRole::current());
    }
}
