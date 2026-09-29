<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Tenancy;

use App\Exception\TenantContextException;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\TestSuite\TestCase;
use Override;

final class TenantContextTest extends TestCase
{
    private TenantContext $context;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->context = TenantContext::instance();
        $this->context->clear();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->context->clear();
        parent::tearDown();
    }

    public function testRequireWorkspaceIdThrowsWhenNoWorkspaceActive(): void
    {
        $this->expectException(TenantContextException::class);

        $this->context->requireWorkspaceId();
    }

    public function testActivateExposesWorkspaceAndRole(): void
    {
        $this->context->activate(5, UserRole::Editor);

        $this->assertSame(5, $this->context->requireWorkspaceId());
        $this->assertSame(UserRole::Editor, $this->context->role);
    }

    public function testClearResetsContext(): void
    {
        $this->context->activate(5, UserRole::Editor);

        $this->context->clear();

        $this->assertNull($this->context->workspaceId);
        $this->assertNull($this->context->role);
    }

    public function testRunScopedRestoresPreviousContext(): void
    {
        $this->context->activate(1, UserRole::Admin);

        $inner = $this->context->runScoped(2, UserRole::Author, fn (): int => $this->context->requireWorkspaceId());

        $this->assertSame(2, $inner);
        $this->assertSame(1, $this->context->requireWorkspaceId());
        $this->assertSame(UserRole::Admin, $this->context->role);
    }

    public function testRunScopedRestoresPreviousContextWhenCallbackThrows(): void
    {
        $this->context->activate(1, UserRole::Admin);

        try {
            $this->context->runScoped(2, null, static function (): never {
                throw new TenantContextException('boom');
            });
        } catch (TenantContextException) {
        }

        $this->assertSame(1, $this->context->requireWorkspaceId());
        $this->assertSame(UserRole::Admin, $this->context->role);
    }
}
