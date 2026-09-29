<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Exception\TenantContextException;
use App\Model\Table\TagsTable;
use App\Model\Tenancy\TenantContext;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\TestCase;
use Override;

final class TenantBehaviorTest extends TestCase
{
    /** @var array<int, string> */
    protected array $fixtures = ['app.Tags'];

    private TagsTable $Tags;

    private TenantContext $context;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->Tags = $this->fetchTable('Tags');
        $this->context = TenantContext::instance();
    }

    public function testSaveStampsActiveWorkspace(): void
    {
        $tag = $this->Tags->newEntity(['label' => 'Stamped']);

        $this->Tags->saveOrFail($tag);

        $this->assertSame(1, $tag->workspace_id);
    }

    public function testFindIsScopedToActiveWorkspace(): void
    {
        $mine = $this->Tags->saveOrFail($this->Tags->newEntity(['label' => 'Mine']));
        $theirs = $this->context->runScoped(
            2,
            null,
            fn (): EntityInterface => $this->Tags->saveOrFail($this->Tags->newEntity(['label' => 'Theirs'])),
        );

        $this->assertNull($this->Tags->find()->where(['id' => $theirs->id])->first());
        $this->assertNotNull($this->Tags->find()->where(['id' => $mine->id])->first());
    }

    public function testSlugCanRepeatAcrossWorkspaces(): void
    {
        $here = $this->Tags->newEntity(['label' => 'Repeated']);
        $this->assertNotFalse($this->Tags->save($here));

        $there = $this->context->runScoped(2, null, function (): EntityInterface|false {
            $tag = $this->Tags->newEntity(['label' => 'Repeated']);

            return $this->Tags->save($tag);
        });

        $this->assertNotFalse($there);
    }

    public function testSaveRejectsForeignWorkspace(): void
    {
        $tag = $this->Tags->newEntity(['label' => 'Foreign']);
        $tag->workspace_id = 2;

        $this->expectException(TenantContextException::class);

        $this->Tags->saveOrFail($tag);
    }

    public function testFindFailsClosedWithoutActiveWorkspace(): void
    {
        $this->context->clear();

        $this->expectException(TenantContextException::class);

        $this->Tags->find()->all()->toList();
    }
}
