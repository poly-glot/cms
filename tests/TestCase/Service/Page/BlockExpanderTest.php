<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Page;

use App\Service\Page\BlockExpander;
use Cake\TestSuite\TestCase;

final class BlockExpanderTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Media', 'app.Blocks'];

    public function testExpandsCalloutToSemanticAside(): void
    {
        $html = new BlockExpander()->expand('<div data-block="1"></div>');

        $this->assertStringContainsString('<aside class="block block--callout block--callout-info">', $html);
        $this->assertStringContainsString('Open Mon', $html);
        $this->assertStringNotContainsString('data-block', $html);
    }

    public function testExpandsQuoteWithAttribution(): void
    {
        $html = new BlockExpander()->expand('<div data-block="2"></div>');

        $this->assertStringContainsString('<figure class="block block--quote">', $html);
        $this->assertStringContainsString('Made by hand.', $html);
        $this->assertStringContainsString('A. Cabinet', $html);
    }

    public function testExpandsImageWithServeUrl(): void
    {
        $html = new BlockExpander()->expand('<div data-block="3"></div>');

        $this->assertStringContainsString('<img src="/cabinet/media/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa?rendition=large"', $html);
    }

    public function testExpandsMultipleReferences(): void
    {
        $html = new BlockExpander()->expand('<p>One</p><div data-block="1"></div><p>Two</p><div data-block="2"></div>');

        $this->assertStringContainsString('block--callout', $html);
        $this->assertStringContainsString('block--quote', $html);
        $this->assertStringContainsString('<p>One</p>', $html);
        $this->assertStringContainsString('<p>Two</p>', $html);
    }

    public function testRemovesUnknownReference(): void
    {
        $html = new BlockExpander()->expand('<p>Before</p><div data-block="999"></div><p>After</p>');

        $this->assertStringNotContainsString('data-block', $html);
        $this->assertStringContainsString('<p>Before</p>', $html);
        $this->assertStringContainsString('<p>After</p>', $html);
    }

    public function testRendersNothingForMissingMedia(): void
    {
        $blocks = $this->fetchTable('Blocks');
        $block = $blocks->newEntity(['block_type' => 'image', 'name' => 'Gone', 'data' => ['media_id' => 999]]);
        $block->author_id = 1;
        $blocks->saveOrFail($block, ['checkRules' => false]);

        $html = new BlockExpander()->expand('<div data-block="' . $block->id . '"></div>');

        $this->assertStringNotContainsString('<img', $html);
    }

    public function testEscapesBlockDataToPreventXss(): void
    {
        $blocks = $this->fetchTable('Blocks');
        $block = $blocks->newEntity([
            'block_type' => 'callout',
            'name' => 'XSS',
            'data' => ['variant' => 'info', 'body' => '<script>alert(1)</script>'],
        ]);
        $block->author_id = 1;
        $blocks->saveOrFail($block);

        $html = new BlockExpander()->expand('<div data-block="' . $block->id . '"></div>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testBodyWithoutReferencesIsUnchanged(): void
    {
        $body = '<p>Plain content.</p>';

        $this->assertSame($body, new BlockExpander()->expand($body));
    }

    public function testNullBodyReturnsEmptyString(): void
    {
        $this->assertSame('', new BlockExpander()->expand(null));
    }

    public function testCtaWithUnsafeUrlRendersWithoutLink(): void
    {
        $blocks = $this->fetchTable('Blocks');
        $block = $blocks->newEntity([
            'block_type' => 'cta',
            'name' => 'Legacy',
            'data' => ['label' => 'Click', 'url' => 'javascript://%0aalert(1)', 'style' => 'primary'],
        ], ['validate' => false]);
        $block->author_id = 1;
        $blocks->saveOrFail($block, ['checkRules' => false]);

        $html = new BlockExpander()->expand('<div data-block="' . $block->id . '"></div>');

        $this->assertStringNotContainsString('<a', $html);
        $this->assertStringNotContainsString('javascript', $html);
        $this->assertStringContainsString('block--cta', $html);
    }

    public function testRemovesInvalidPlaceholder(): void
    {
        $html = new BlockExpander()->expand('<p>Keep</p><div data-block=""></div>');

        $this->assertStringNotContainsString('data-block', $html);
        $this->assertStringContainsString('<p>Keep</p>', $html);
    }
}
