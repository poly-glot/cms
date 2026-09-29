<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Block;
use App\Model\Table\BlocksTable;
use Cake\TestSuite\TestCase;

final class BlocksTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Media', 'app.Pages', 'app.Blocks', 'app.PageBlocks'];
    private BlocksTable $Blocks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Blocks = $this->fetchTable('Blocks');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function newCallout(array $data): Block
    {
        $block = $this->Blocks->newEntity(['block_type' => 'callout', 'name' => 'X', 'data' => $data]);
        $block->author_id = 1;

        return $block;
    }

    public function testValidatesCalloutDataHappyPath(): void
    {
        $block = $this->newCallout(['variant' => 'info', 'body' => 'Hello.']);

        $saved = $this->Blocks->save($block);

        $this->assertNotFalse($saved);
        $this->assertSame('callout', $saved->block_type);
        $this->assertSame('info', $saved->data['variant']);
    }

    public function testRejectsCalloutMissingRequiredKey(): void
    {
        $block = $this->newCallout(['variant' => 'info']);

        $this->assertFalse($this->Blocks->save($block));
        $this->assertArrayHasKey('data', $block->getErrors());
    }

    public function testRejectsUnknownVariant(): void
    {
        $block = $this->newCallout(['variant' => 'banana', 'body' => 'Hi.']);

        $this->assertFalse($this->Blocks->save($block));
        $this->assertArrayHasKey('data', $block->getErrors());
    }

    public function testRejectsCtaWithBadStyle(): void
    {
        $block = $this->Blocks->newEntity([
            'block_type' => 'cta',
            'name' => 'Go',
            'data' => ['label' => 'Go', 'url' => '/about', 'style' => 'huge'],
        ]);
        $block->author_id = 1;

        $this->assertFalse($this->Blocks->save($block));
        $this->assertArrayHasKey('data', $block->getErrors());
    }

    public function testRejectsUnknownDataKey(): void
    {
        $block = $this->newCallout(['variant' => 'info', 'body' => 'Hi.', 'rogue' => 'x']);

        $this->assertFalse($this->Blocks->save($block));
        $this->assertArrayHasKey('data', $block->getErrors());
    }

    public function testImageRequiresExistingMedia(): void
    {
        $block = $this->Blocks->newEntity([
            'block_type' => 'image',
            'name' => 'Pic',
            'data' => ['media_id' => 999],
        ]);
        $block->author_id = 1;

        $this->assertFalse($this->Blocks->save($block));
        $this->assertArrayHasKey('data', $block->getErrors());
    }

    public function testDeleteGuardBlocksReferencedBlock(): void
    {
        $block = $this->Blocks->get(1);

        $this->assertFalse($this->Blocks->delete($block));
        $this->assertTrue($this->Blocks->exists(['id' => 1]));
    }

    public function testDeleteAllowsUnreferencedBlock(): void
    {
        $block = $this->Blocks->get(2);

        $this->assertTrue($this->Blocks->delete($block));
        $this->assertFalse($this->Blocks->exists(['id' => 2]));
    }

    public function testFindForPickerShape(): void
    {
        $rows = $this->Blocks->find('forPicker')->toArray();

        $this->assertCount(3, $rows);
        $this->assertNotNull($rows[0]->id);
        $this->assertNotNull($rows[0]->block_type);
        $this->assertNotNull($rows[0]->name);
    }

    public function testFindForLibraryFiltersByType(): void
    {
        $rows = $this->Blocks->find('forLibrary', blockType: 'quote')->toArray();

        $this->assertCount(1, $rows);
        $this->assertSame('Founder quote', $rows[0]->name);
    }

    public function testRejectsJavascriptSchemeInCtaUrl(): void
    {
        $block = $this->Blocks->newEntity([
            'block_type' => 'cta',
            'name' => 'Evil',
            'data' => ['label' => 'Click', 'url' => 'javascript://%0aalert(document.domain)', 'style' => 'primary'],
        ]);
        $block->author_id = 1;

        $this->assertFalse($this->Blocks->save($block));
        $this->assertArrayHasKey('data', $block->getErrors());
    }

    public function testRejectsProtocolRelativeCtaUrl(): void
    {
        $block = $this->Blocks->newEntity([
            'block_type' => 'cta',
            'name' => 'Sneaky',
            'data' => ['label' => 'Click', 'url' => '//evil.example', 'style' => 'primary'],
        ]);
        $block->author_id = 1;

        $this->assertFalse($this->Blocks->save($block));
    }
}
