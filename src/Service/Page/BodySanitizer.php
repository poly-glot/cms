<?php

declare(strict_types=1);

namespace App\Service\Page;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Allowlist sanitizer for rich-text page/post body HTML, applied at the save
 * boundary so a hand-crafted PATCH cannot bypass the editor's client schema.
 *
 * The allowlist matches exactly what the Tiptap editor emits (StarterKit nodes
 * plus the custom block placeholder and image). `data-block` is registered as a
 * numeric attribute so reusable-block references survive untouched for
 * BlockExpander; every other attribute, every script/style/iframe/svg element,
 * and every non-http(s)/mailto URL scheme is stripped.
 */
final class BodySanitizer
{
    private const string ALLOWED = 'p[style],br,hr,strong,em,s,code,pre,blockquote,'
        . 'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],'
        . 'ul,ol[start],li,a[href],img[src|alt],div[data-block],'
        . 'table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan]';

    private static ?HTMLPurifier $purifier = null;

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        return $this->purifier()->purify($html);
    }

    private function purifier(): HTMLPurifier
    {
        if (self::$purifier instanceof HTMLPurifier) {
            return self::$purifier;
        }

        $cacheDir = CACHE . 'htmlpurifier';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0o775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', self::ALLOWED);
        $config->set('CSS.AllowedProperties', ['text-align']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', []);
        $config->set('Cache.SerializerPath', $cacheDir);
        $config->set('HTML.DefinitionID', 'cms-body');
        $config->set('HTML.DefinitionRev', 3);

        $definition = $config->maybeGetRawHTMLDefinition();
        if ($definition !== null) {
            $definition->addAttribute('div', 'data-block', 'Number');
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
