<?php

declare(strict_types=1);

namespace App\Service\Page;

use DOMDocument;

/**
 * Shared parsing of `<div data-block="ID">` references inside page body HTML.
 * Used by BlockExpander (render) and BlockUsageIndexer (usage tracking).
 */
final class BlockReferenceScanner
{
    private const string ROOT_ID = 'cabinet-block-root';

    /**
     * Referenced block ids, in document order, de-duplicated.
     *
     * @return list<int>
     */
    public static function ids(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $ids = [];
        foreach (self::parse($html)->getElementsByTagName('div') as $div) {
            $value = $div->getAttribute('data-block');
            if ($value !== '' && ctype_digit($value)) {
                $ids[(int) $value] = true;
            }
        }

        return array_keys($ids);
    }

    /**
     * Parse an HTML fragment under a single wrapper element so it round-trips
     * without an implied html/body or a doctype. UTF-8 is forced so non-ASCII
     * body content survives.
     */
    public static function parse(string $html): DOMDocument
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="' . self::ROOT_ID . '">' . $html . '</div>',
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }
}
