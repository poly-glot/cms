<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WorkspacesTable;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;
use Override;

final class WorkspacesTableTest extends TestCase
{
    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships'];

    private WorkspacesTable $Workspaces;

    #[Override]
    protected function getFixtureStrategy(): TransactionStrategy
    {
        return new TransactionStrategy();
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->Workspaces = $this->fetchTable('Workspaces');
    }

    public function testCreatesWorkspaceWithValidSlug(): void
    {
        $workspace = $this->Workspaces->newEntity(['name' => 'Studio North', 'slug' => 'studio-north']);

        $this->assertNotFalse($this->Workspaces->save($workspace));
        $this->assertEmpty($workspace->getErrors());
    }

    public function testRejectsDuplicateSlug(): void
    {
        $workspace = $this->Workspaces->newEntity(['name' => 'Another Cabinet', 'slug' => 'cabinet']);

        $this->assertFalse($this->Workspaces->save($workspace));
        $this->assertArrayHasKey('slug', $workspace->getErrors());
    }

    public function testRejectsNonKebabSlug(): void
    {
        $workspace = $this->Workspaces->newEntity(['name' => 'Bad Slug', 'slug' => 'Bad Slug!']);

        $this->assertFalse($this->Workspaces->save($workspace));
        $this->assertArrayHasKey('slug', $workspace->getErrors());
    }

    public function testFindBySlugReturnsMatch(): void
    {
        $workspace = $this->Workspaces->find('bySlug', slug: 'atelier')->first();

        $this->assertNotNull($workspace);
        $this->assertSame('Atelier', $workspace->name);
    }
}
