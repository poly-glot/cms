<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\CollectionFieldType;
use App\Model\Enum\ContentType;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ContentTypeTest extends TestCase
{
    public function testLabels(): void
    {
        $this->assertSame('Pages', ContentType::Pages->label());
        $this->assertSame('Posts', ContentType::Posts->label());
    }

    /**
     * @return array<string, array{ContentType, list<string>}>
     */
    public static function contentColumns(): array
    {
        return [
            'pages' => [ContentType::Pages, [
                'id', 'workspace_id', 'author_id', 'title', 'slug', 'body', 'status',
                'template', 'visibility', 'parent_id', 'position', 'comments_enabled',
                'published_at', 'data', 'created', 'modified',
            ]],
            'posts' => [ContentType::Posts, [
                'id', 'workspace_id', 'author_id', 'title', 'slug', 'excerpt', 'body',
                'status', 'comments_enabled', 'published_at', 'data', 'created', 'modified',
            ]],
        ];
    }

    /**
     * @param list<string> $columns
     */
    #[DataProvider('contentColumns')]
    public function testReservedFieldNamesCoverEveryColumn(ContentType $type, array $columns): void
    {
        $reserved = $type->reservedFieldNames();

        foreach ($columns as $column) {
            $this->assertContains($column, $reserved);
        }
    }

    public function testPostsReservedNamesOmitPagesOnlyColumns(): void
    {
        $reserved = ContentType::Posts->reservedFieldNames();

        $this->assertContains('excerpt', $reserved);
        $this->assertNotContains('template', $reserved);
        $this->assertNotContains('parent_id', $reserved);
        $this->assertNotContains('position', $reserved);
    }

    public function testAllowedFieldTypesExcludeReferenceAndTags(): void
    {
        foreach (ContentType::cases() as $type) {
            $allowed = $type->allowedFieldTypes();

            $this->assertNotContains(CollectionFieldType::Reference, $allowed);
            $this->assertNotContains(CollectionFieldType::Tags, $allowed);
            $this->assertContains(CollectionFieldType::RichText, $allowed);
            $this->assertContains(CollectionFieldType::Repeater, $allowed);
        }
    }

    public function testAllowedFieldTypesAreEveryTypeButTwo(): void
    {
        $this->assertCount(count(CollectionFieldType::cases()) - 2, ContentType::Pages->allowedFieldTypes());
    }
}
