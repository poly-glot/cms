<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Workspace;

use App\Exception\WorkspaceProvisionException;
use App\Model\Enum\UserRole;
use App\Service\Workspace\WorkspaceProvisioner;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;
use Override;

final class WorkspaceProvisionerTest extends TestCase
{
    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships'];

    #[Override]
    protected function getFixtureStrategy(): TransactionStrategy
    {
        return new TransactionStrategy();
    }

    public function testProvisionCreatesWorkspaceAndAdminMembership(): void
    {
        $result = new WorkspaceProvisioner()->provision(1, 'Ironworks', 'ironworks');

        $this->assertSame('Ironworks', $result['workspace']->name);
        $this->assertSame('ironworks', $result['workspace']->slug);
        $this->assertSame(UserRole::Admin->value, $result['membership']->role);
        $this->assertSame(1, $result['membership']->user_id);
        $this->assertSame($result['workspace']->id, $result['membership']->workspace_id);

        $memberships = $this->fetchTable('Memberships');
        $this->assertTrue($memberships->exists(['user_id' => 1, 'workspace_id' => $result['workspace']->id]));
    }

    public function testProvisionRejectsReservedSlug(): void
    {
        try {
            new WorkspaceProvisioner()->provision(1, 'Admin Area', 'admin');
            $this->fail('Expected a WorkspaceProvisionException for a reserved slug.');
        } catch (WorkspaceProvisionException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors);
        }

        $workspaces = $this->fetchTable('Workspaces');
        $this->assertNull($workspaces->find('bySlug', slug: 'admin')->first());
    }

    public function testProvisionRejectsDuplicateSlug(): void
    {
        $this->expectException(WorkspaceProvisionException::class);

        new WorkspaceProvisioner()->provision(1, 'Cabinet Two', 'cabinet');
    }
}
