<?php

declare(strict_types=1);

namespace App\Service\Page;

use App\Model\Entity\Block;
use App\Model\Entity\Media;
use App\Model\Enum\BlockType;
use App\Model\Tenancy\TenantContext;
use App\Model\Validation\SafeUrl;
use App\Service\Media\MediaFileResponder;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use DOMElement;
use DOMNode;

/**
 * Expands `<div data-block="ID">` placeholders in page body HTML into escaped
 * semantic block markup. Unknown or deleted references are dropped silently.
 */
final class BlockExpander
{
    use LocatorAwareTrait;

    public function expand(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        // Run the DOM pass whenever any placeholder is present — including
        // invalid ones (empty/non-numeric data-block) — so none leak to output.
        if (!str_contains($body, 'data-block')) {
            return $body;
        }

        $ids = BlockReferenceScanner::ids($body);
        $blocks = $ids === [] ? [] : $this->loadBlocks($ids);
        $media = $this->loadMedia($blocks);

        $document = BlockReferenceScanner::parse($body);
        $root = $document->documentElement;
        if ($root === null) {
            return $body;
        }

        foreach ($this->placeholders($root) as $node) {
            $id = (int) $node->getAttribute('data-block');
            $rendered = isset($blocks[$id]) ? $this->render($blocks[$id], $media) : '';
            $parent = $node->parentNode;
            if ($parent === null) {
                continue;
            }
            if ($rendered === '') {
                $parent->removeChild($node);

                continue;
            }
            $this->replaceWithHtml($node, $rendered);
        }

        return $this->innerHtml($root);
    }

    /**
     * @param list<int> $ids
     * @return array<int, Block>
     */
    private function loadBlocks(array $ids): array
    {
        $blocks = [];
        foreach ($this->fetchTable('Blocks')->find()->where(['id IN' => $ids]) as $block) {
            $blocks[$block->id] = $block;
        }

        return $blocks;
    }

    /**
     * @param array<int, Block> $blocks
     * @return array<int, Media>
     */
    private function loadMedia(array $blocks): array
    {
        $mediaIds = [];
        foreach ($blocks as $block) {
            if ($block->type->referencesMedia()) {
                $mediaId = $this->mediaId($block->data);
                if ($mediaId > 0) {
                    $mediaIds[] = $mediaId;
                }
            }
        }
        if ($mediaIds === []) {
            return [];
        }

        $media = [];
        foreach ($this->fetchTable('Media')->find()->where(['id IN' => $mediaIds]) as $item) {
            $media[$item->id] = $item;
        }

        return $media;
    }

    /**
     * Collect placeholder nodes into a static array before mutating the tree.
     *
     * @return list<DOMElement>
     */
    private function placeholders(DOMNode $root): array
    {
        $document = $root->ownerDocument;
        if ($document === null) {
            return [];
        }

        $nodes = [];
        foreach ($document->getElementsByTagName('div') as $div) {
            if ($div->hasAttribute('data-block')) {
                $nodes[] = $div;
            }
        }

        return $nodes;
    }

    /**
     * @param array<int, Media> $media
     */
    private function render(Block $block, array $media): string
    {
        return match ($block->type) {
            BlockType::Callout => sprintf(
                '<aside class="block block--callout block--callout-%s">%s</aside>',
                $this->esc($block->data, 'variant'),
                $this->esc($block->data, 'body'),
            ),
            BlockType::Quote => $this->renderQuote($block->data),
            BlockType::Statistic => $this->renderStatistic($block->data),
            BlockType::Cta => $this->renderCta($block->data),
            BlockType::Image => $this->renderImage($block->data, $media),
            BlockType::File => $this->renderFile($block->data, $media),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderCta(array $data): string
    {
        $label = $this->esc($data, 'label');
        $url = $data['url'] ?? '';

        // Defense in depth: neutralise any unsafe scheme that slipped past
        // validation (e.g. legacy rows) by dropping the anchor entirely.
        if (!is_string($url) || !SafeUrl::check($url)) {
            return sprintf('<p class="block block--cta">%s</p>', $label);
        }

        return sprintf(
            '<p class="block block--cta"><a class="block__cta block__cta--%s" href="%s">%s</a></p>',
            $this->esc($data, 'style'),
            htmlspecialchars($url, \ENT_QUOTES, 'UTF-8'),
            $label,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderQuote(array $data): string
    {
        $attribution = $this->esc($data, 'attribution');
        $caption = $attribution === '' ? '' : sprintf('<figcaption>%s</figcaption>', $attribution);

        return sprintf(
            '<figure class="block block--quote"><blockquote>%s</blockquote>%s</figure>',
            $this->esc($data, 'text'),
            $caption,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderStatistic(array $data): string
    {
        $caption = $this->esc($data, 'caption');
        $captionHtml = $caption === '' ? '' : sprintf('<span class="block__stat-caption">%s</span>', $caption);

        return sprintf(
            '<div class="block block--statistic"><span class="block__stat-value">%s</span>'
            . '<span class="block__stat-label">%s</span>%s</div>',
            $this->esc($data, 'value'),
            $this->esc($data, 'label'),
            $captionHtml,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, Media> $media
     */
    private function renderImage(array $data, array $media): string
    {
        $mediaId = $this->mediaId($data);
        if (!isset($media[$mediaId])) {
            return '';
        }

        $alt = $this->esc($data, 'alt');
        if ($alt === '') {
            $alt = htmlspecialchars((string) $media[$mediaId]->alt, \ENT_QUOTES, 'UTF-8');
        }
        $caption = $this->esc($data, 'caption');
        $captionHtml = $caption === '' ? '' : sprintf('<figcaption>%s</figcaption>', $caption);

        $rendition = $data['rendition'] ?? null;
        $rendition = is_string($rendition) ? $rendition : 'large';

        return sprintf(
            '<figure class="block block--image"><img src="%s" alt="%s">%s</figure>',
            $this->mediaUrl($media[$mediaId], $rendition),
            $alt,
            $captionHtml,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, Media> $media
     */
    private function renderFile(array $data, array $media): string
    {
        $mediaId = $this->mediaId($data);
        if (!isset($media[$mediaId])) {
            return '';
        }

        $label = $this->esc($data, 'label');
        if ($label === '') {
            $label = htmlspecialchars((string) $media[$mediaId]->name, \ENT_QUOTES, 'UTF-8');
        }

        return sprintf(
            '<p class="block block--file"><a href="%s" download>%s</a></p>',
            $this->mediaUrl($media[$mediaId]),
            $label,
        );
    }

    private function mediaUrl(Media $media, ?string $rendition = null): string
    {
        if ($rendition !== null && in_array($rendition, ['large', 'medium', 'small', 'thumb'], true)) {
            $publicBase = Configure::read('App.media.publicBase');
            if (is_string($publicBase) && $publicBase !== '') {
                $direct = rtrim($publicBase, '/') . '/' . new MediaFileResponder()->publicRenditionPath($media, $rendition);

                return htmlspecialchars($direct, \ENT_QUOTES, 'UTF-8');
            }
        }

        $slug = TenantContext::instance()->workspaceSlug;
        $prefix = $slug !== null && $slug !== '' ? '/' . $slug : '';
        $url = $prefix . '/media/' . $media->filename;
        if ($rendition !== null && in_array($rendition, ['large', 'medium', 'small', 'thumb'], true)) {
            $url .= '?rendition=' . $rendition;
        }

        return htmlspecialchars($url, \ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function esc(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_scalar($value) ? htmlspecialchars((string) $value, \ENT_QUOTES, 'UTF-8') : '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mediaId(array $data): int
    {
        $value = $data['media_id'] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }

    private function replaceWithHtml(DOMElement $node, string $html): void
    {
        $parent = $node->parentNode;
        $document = $node->ownerDocument;
        if ($parent === null || $document === null) {
            return;
        }

        $fragmentRoot = BlockReferenceScanner::parse($html)->documentElement;
        if ($fragmentRoot === null) {
            $parent->removeChild($node);

            return;
        }

        foreach (iterator_to_array($fragmentRoot->childNodes) as $child) {
            $parent->insertBefore($document->importNode($child, true), $node);
        }
        $parent->removeChild($node);
    }

    private function innerHtml(DOMNode $node): string
    {
        $document = $node->ownerDocument;
        if ($document === null) {
            return '';
        }

        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= (string) $document->saveHTML($child);
        }

        return $html;
    }
}
