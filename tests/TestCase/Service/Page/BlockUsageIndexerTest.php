<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Page;

use App\Service\Page\BlockUsageIndexer;
use Cake\TestSuite\TestCase;

final class BlockUsageIndexerTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Media', 'app.Pages', 'app.Blocks', 'app.PageBlocks'];

    private function pageBlockCount(int $pageId): int
    {
        return $this->fetchTable('PageBlocks')->find()->where(['page_id' => $pageId])->count();
    }

    public function testReindexSyncsReferences(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->body = '<p>Intro</p><div data-block="2"></div>';

        new BlockUsageIndexer()->reindex($page);

        $links = $this->fetchTable('PageBlocks')->find()->where(['page_id' => 2])->all()->toList();
        $this->assertCount(1, $links);
        $this->assertSame(2, $links[0]->block_id);
    }

    public function testReindexRemovesDroppedReferences(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $page->body = '<p>No blocks any more.</p>';

        new BlockUsageIndexer()->reindex($page);

        $this->assertSame(0, $this->pageBlockCount(1));
    }

    public function testReindexDeduplicatesRepeatedReference(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->body = '<div data-block="1"></div><div data-block="1"></div>';

        new BlockUsageIndexer()->reindex($page);

        $this->assertSame(1, $this->pageBlockCount(2));
    }

    public function testReindexIgnoresNonExistentBlock(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->body = '<div data-block="999"></div>';

        new BlockUsageIndexer()->reindex($page);

        $this->assertSame(0, $this->pageBlockCount(2));
    }
}
