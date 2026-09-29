<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\PostStatus;
use PHPUnit\Framework\TestCase;

final class PostStatusTest extends TestCase
{
    public function testIsPubliclyVisibleOnlyForLive(): void
    {
        $this->assertTrue(PostStatus::Live->isPubliclyVisible());
        $this->assertFalse(PostStatus::Draft->isPubliclyVisible());
    }

    public function testScheduledIsNotPubliclyVisible(): void
    {
        $this->assertFalse(PostStatus::Scheduled->isPubliclyVisible());
    }
}
