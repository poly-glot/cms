<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\BlockType;
use PHPUnit\Framework\TestCase;

final class BlockTypeTest extends TestCase
{
    public function testLabelReturnsHumanString(): void
    {
        $this->assertSame('Callout', BlockType::Callout->label());
        $this->assertSame('Call to action', BlockType::Cta->label());
        $this->assertSame('File', BlockType::File->label());
    }

    public function testRequiredKeysPerType(): void
    {
        $this->assertSame(['variant', 'body'], BlockType::Callout->requiredKeys());
        $this->assertSame(['text'], BlockType::Quote->requiredKeys());
        $this->assertSame(['value', 'label'], BlockType::Statistic->requiredKeys());
        $this->assertSame(['label', 'url', 'style'], BlockType::Cta->requiredKeys());
        $this->assertSame(['media_id'], BlockType::Image->requiredKeys());
        $this->assertSame(['media_id'], BlockType::File->requiredKeys());
    }

    public function testReferencesMediaOnlyForImageAndFile(): void
    {
        $this->assertTrue(BlockType::Image->referencesMedia());
        $this->assertTrue(BlockType::File->referencesMedia());
        $this->assertFalse(BlockType::Callout->referencesMedia());
        $this->assertFalse(BlockType::Cta->referencesMedia());
    }

    public function testChipLabelOverridesTwoCasesAndFallsBackToLabel(): void
    {
        $this->assertSame('Call to Action', BlockType::Cta->chipLabel());
        $this->assertSame('File Link', BlockType::File->chipLabel());
        $this->assertSame('Callout', BlockType::Callout->chipLabel());
        $this->assertSame('Statistic', BlockType::Statistic->chipLabel());
    }

    public function testEveryCaseHasANonEmptyDescription(): void
    {
        foreach (BlockType::cases() as $type) {
            $this->assertNotSame('', $type->description());
        }
    }
}
