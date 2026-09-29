<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\ContentTypeFieldSchema;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use App\Policy\EditorialPolicy;
use Authorization\IdentityInterface;
use PHPUnit\Framework\TestCase;

final class EditorialPolicyTest extends TestCase
{
    public function testAdminCanEdit(): void
    {
        $this->assertAccess('admin', true);
    }

    public function testEditorCanEdit(): void
    {
        $this->assertAccess('editor', true);
    }

    public function testAuthorCannotEdit(): void
    {
        $this->assertAccess('author', false);
    }

    public function testContributorCannotEdit(): void
    {
        $this->assertAccess('contributor', false);
    }

    private function assertAccess(string $role, bool $expected): void
    {
        $identity = $this->identity($role);
        $policy = new EditorialPolicy();
        $schema = new ContentTypeFieldSchema();

        $this->assertSame($expected, $policy->canEdit($identity, $schema));
    }

    private function identity(string $role): IdentityInterface
    {
        TenantContext::instance()->activate(1, UserRole::fromValue($role), 'cabinet');

        return $this->createStub(IdentityInterface::class);
    }
}
