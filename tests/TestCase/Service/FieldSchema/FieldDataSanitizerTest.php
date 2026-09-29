<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\FieldSchema;

use App\Model\Entity\Collection;
use App\Service\FieldSchema\FieldDataSanitizer;
use PHPUnit\Framework\TestCase;

final class FieldDataSanitizerTest extends TestCase
{
    private FieldDataSanitizer $sanitizer;
    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new FieldDataSanitizer();
        $this->collection = new Collection(['field_schema' => [
            ['name' => 'bio', 'label' => 'Bio', 'type' => 'rich_text'],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
        ]]);
    }

    public function testStripsScriptFromRichTextField(): void
    {
        $out = $this->sanitizer->clean($this->collection->fields, [
            'bio' => '<p>Hi</p><script>alert(1)</script>',
            'name' => 'plain',
        ]);

        $bio = $out['bio'];
        $this->assertIsString($bio);
        $this->assertStringNotContainsString('<script', $bio);
        $this->assertStringContainsString('Hi', $bio);
        $this->assertSame('plain', $out['name']);
    }

    public function testLeavesNonRichTextValuesUntouched(): void
    {
        $out = $this->sanitizer->clean($this->collection->fields, ['name' => '<b>kept verbatim</b>']);

        $this->assertSame('<b>kept verbatim</b>', $out['name']);
    }

    public function testSanitizesRichTextInsideRepeaterRows(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'sections', 'label' => 'Sections', 'type' => 'repeater', 'fields' => [
                ['name' => 'body', 'label' => 'Body', 'type' => 'rich_text'],
            ]],
        ]]);

        $out = $this->sanitizer->clean($collection->fields, ['sections' => [
            ['body' => '<p>ok</p><script>alert(1)</script>'],
        ]]);

        $sections = $out['sections'];
        $this->assertIsArray($sections);
        $row = $sections[0];
        $this->assertIsArray($row);
        $body = $row['body'];
        $this->assertIsString($body);
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringContainsString('ok', $body);
    }
}
