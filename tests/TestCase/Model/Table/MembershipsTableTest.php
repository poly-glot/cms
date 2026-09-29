<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Membership;
use App\Model\Table\MembershipsTable;
use Cake\TestSuite\TestCase;
use Override;

final class MembershipsTableTest extends TestCase
{
    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships'];

    private MembershipsTable $Memberships;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->Memberships = $this->fetchTable('Memberships');
    }

    public function testRejectsDuplicateUserInSameWorkspace(): void
    {
        $membership = $this->buildMembership(workspaceId: 1, userId: 1, role: 'editor');

        $this->assertFalse($this->Memberships->save($membership));
        $this->assertArrayHasKey('user_id', $membership->getErrors());
    }

    public function testAllowsSameUserInDifferentWorkspaces(): void
    {
        $membership = $this->buildMembership(workspaceId: 2, userId: 2, role: 'author');

        $this->assertNotFalse($this->Memberships->save($membership));
        $this->assertEmpty($membership->getErrors());
    }

    public function testRejectsInvalidRole(): void
    {
        $membership = $this->buildMembership(workspaceId: 2, userId: 3, role: 'overlord');

        $this->assertFalse($this->Memberships->save($membership));
        $this->assertArrayHasKey('role', $membership->getErrors());
    }

    public function testFindForUserReturnsWorkspacesOrderedByName(): void
    {
        $memberships = $this->Memberships->find('forUser', userId: 1)->all()->toList();

        $this->assertCount(2, $memberships);
        $this->assertSame('Atelier', $memberships[0]->workspace->name);
        $this->assertSame('Cabinet', $memberships[1]->workspace->name);
    }

    public function testWorkspaceIdAndUserIdAreNotMassAssignable(): void
    {
        $membership = $this->Memberships->newEntity(['workspace_id' => 1, 'user_id' => 1, 'role' => 'editor']);

        $this->assertNull($membership->workspace_id);
        $this->assertNull($membership->user_id);
        $this->assertSame('editor', $membership->role);
    }

    public function testRejectsReassigningMembershipToAnotherWorkspace(): void
    {
        $membership = $this->Memberships->get(1);
        $membership->workspace_id = 2;

        $this->assertFalse($this->Memberships->save($membership));
        $this->assertArrayHasKey('workspace_id', $membership->getErrors());
    }

    private function buildMembership(int $workspaceId, int $userId, string $role): Membership
    {
        $membership = $this->Memberships->newEntity(['role' => $role]);
        $membership->workspace_id = $workspaceId;
        $membership->user_id = $userId;

        return $membership;
    }
}
