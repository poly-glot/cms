<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Media;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use App\Policy\MediaPolicy;
use Authorization\IdentityInterface;
use PHPUnit\Framework\TestCase;

final class MediaPolicyTest extends TestCase
{
    public function testAnyAuthenticatedUserCanViewAndUpdate(): void
    {
        $contributor = $this->identity('contributor');
        $policy = new MediaPolicy();

        $this->assertTrue($policy->canView($contributor, new Media()));
        $this->assertTrue($policy->canUpdate($contributor, new Media()));
    }

    public function testOnlyEditorialCanDelete(): void
    {
        $policy = new MediaPolicy();

        $this->assertTrue($policy->canDelete($this->identity('admin'), new Media()));
        $this->assertTrue($policy->canDelete($this->identity('editor'), new Media()));
        $this->assertFalse($policy->canDelete($this->identity('author'), new Media()));
        $this->assertFalse($policy->canDelete($this->identity('contributor'), new Media()));
    }

    private function identity(string $role): IdentityInterface
    {
        TenantContext::instance()->activate(1, UserRole::fromValue($role), 'cabinet');

        return $this->createStub(IdentityInterface::class);
    }
}
