<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Page;

use App\Service\Page\BodySanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BodySanitizerTest extends TestCase
{
    private BodySanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new BodySanitizer();
    }

    /**
     * @return array<string, array{string, list<string>, list<string>}>
     */
    public static function sanitizedBodies(): array
    {
        return [
            'script element and payload' => [
                '<p>hi</p><script>alert(1)</script>',
                ['hi'],
                ['<script', 'alert(1)'],
            ],
            'inline event handlers' => [
                '<p onclick="alert(1)" onmouseover="x()">hover</p>',
                ['hover'],
                ['onclick', 'onmouseover'],
            ],
            'image onerror on a valid image' => [
                '<img src="/media/a.png" alt="cap" onerror="alert(1)">',
                ['src="/media/a.png"', 'alt="cap"'],
                ['onerror'],
            ],
            'javascript anchor scheme' => [
                '<a href="javascript:alert(1)">go</a>',
                ['go'],
                ['javascript:', 'href'],
            ],
            'safe anchor schemes' => [
                '<a href="https://example.com">x</a><a href="mailto:a@b.c">y</a><a href="/about">z</a>',
                ['href="https://example.com"', 'href="mailto:a@b.c"', 'href="/about"'],
                [],
            ],
            'image link' => [
                '<a href="https://example.com"><img src="/cabinet/media/uuid?rendition=large" alt="cap"></a>',
                ['href="https://example.com"', 'src="/cabinet/media/uuid?rendition=large"'],
                [],
            ],
            'text-align kept, other css stripped' => [
                '<p style="text-align:center; background:url(javascript:alert(1)); width:expression(alert(1))">x</p>',
                ['text-align:center'],
                ['javascript', 'expression', 'background'],
            ],
            'tables with spans, cruft and handlers stripped' => [
                '<table style="min-width:50px"><colgroup><col style="min-width:25px"></colgroup>'
                . '<tbody><tr><th colspan="2"><p>H</p></th></tr>'
                . '<tr><td onclick="alert(1)" style="background:red"><p>a</p></td><td><p>b</p></td></tr></tbody></table>',
                ['<table>', 'colspan="2"', '<th', '<td'],
                ['onclick', '<colgroup', 'min-width'],
            ],
            'style attribute with only disallowed properties' => [
                '<p style="color:red; font-size:40px">x</p>',
                ['x'],
                ['style='],
            ],
            'non-positive colspan' => [
                '<table><tbody><tr><td colspan="-1"><p>a</p></td><td colspan="0"><p>b</p></td></tr></tbody></table>',
                [],
                ['colspan="-1"', 'colspan="0"'],
            ],
            'obfuscated scheme variants' => [
                '<a href="jAvAsCrIpT:alert(1)">a</a>'
                . '<a href="java&#9;script:alert(2)">b</a>'
                . '<a href="&#106;avascript:alert(3)">c</a>'
                . '<a href="vbscript:msgbox(1)">d</a>',
                [],
                ['javascript:', 'vbscript:'],
            ],
            'iframe, object and embed' => [
                '<iframe src="//evil/x"></iframe><object data="x"></object><embed src="x">',
                [],
                ['<iframe', '<object', '<embed'],
            ],
            'svg and data-svg vector' => [
                '<svg><script>alert(1)</script></svg><img src="data:image/svg+xml,<svg onload=alert(1)>">',
                [],
                ['<svg', 'onload', '<script', 'data:'],
            ],
            'style, base, meta and link elements' => [
                '<style>@import url("//evil")</style>'
                . '<base href="https://evil/">'
                . '<meta http-equiv="refresh" content="0;url=//evil">'
                . '<link rel="stylesheet" href="//evil/x.css">'
                . '<p>x</p>',
                ['x'],
                ['<style', '<base', '<meta', '<link'],
            ],
            'attribute breakout mxss' => [
                '<p title="</p><img src=x onerror=alert(1)>">x</p>',
                [],
                ['onerror'],
            ],
            'comment breakout mxss' => [
                '<!--><img src=x onerror=alert(1)>-->',
                [],
                ['onerror'],
            ],
            'data uri image' => [
                '<img src="data:image/png;base64,iVBORw0KGgo=">',
                [],
                ['data:'],
            ],
            'image style and srcset attributes' => [
                '<img src="/media/a.png" style="x" srcset="javascript:alert(1)">',
                ['src="/media/a.png"'],
                ['style=', 'srcset', 'javascript:'],
            ],
            'numeric block placeholder' => [
                '<div data-block="42"></div>',
                ['data-block="42"'],
                [],
            ],
            'non-numeric block id' => [
                '<div data-block="x"></div><div data-block=""></div>',
                [],
                ['data-block'],
            ],
            'div attributes other than block' => [
                '<div onload="alert(1)" class="evil">text</div>',
                ['text'],
                ['onload'],
            ],
            'allowed formatting' => [
                '<p><strong>a</strong> <em>b</em> <s>c</s></p><h2>T</h2><blockquote>q</blockquote><ul><li>i</li></ul>',
                ['<strong>a</strong>', '<em>b</em>', '<s>c</s>', '<h2>T</h2>', '<blockquote>q</blockquote>', '<li>i</li>'],
                [],
            ],
            'relative media image' => [
                '<img src="/media/photo.png" alt="A bench">',
                ['src="/media/photo.png"', 'alt="A bench"'],
                [],
            ],
            'ordered list start kept, type dropped' => [
                '<ol start="3" type="a"><li>x</li></ol>',
                ['start="3"'],
                ['type='],
            ],
            'utf-8 body' => [
                '<p>café — naïve 日本語</p>',
                ['café — naïve 日本語'],
                [],
            ],
        ];
    }

    /**
     * @param list<string> $kept
     * @param list<string> $stripped
     */
    #[DataProvider('sanitizedBodies')]
    public function testCleanKeepsAllowedMarkupAndStripsTheRest(string $body, array $kept, array $stripped): void
    {
        $clean = $this->sanitizer->clean($body);

        foreach ($kept as $fragment) {
            $this->assertStringContainsString($fragment, $clean);
        }
        foreach ($stripped as $fragment) {
            $this->assertStringNotContainsStringIgnoringCase($fragment, $clean);
        }
    }

    public function testReturnsEmptyForBlankInput(): void
    {
        $this->assertSame('', $this->sanitizer->clean('   '));
        $this->assertSame('', $this->sanitizer->clean(''));
    }

    public function testIsIdempotent(): void
    {
        $once = $this->sanitizer->clean('<p><strong>a</strong></p><div data-block="5"></div><img src="/media/x.png" alt="y">');

        $this->assertSame($once, $this->sanitizer->clean($once));
    }
}
