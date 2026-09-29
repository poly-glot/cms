<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\BlockHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

final class BlockHelperTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Media', 'app.Blocks'];
    private BlockHelper $Block;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Block = new BlockHelper(new View());
    }

    public function testExpandNullReturnsEmptyString(): void
    {
        $this->assertSame('', $this->Block->expand(null));
    }

    public function testExpandLeavesPlainBodyUnchanged(): void
    {
        $this->assertSame('<p>Plain.</p>', $this->Block->expand('<p>Plain.</p>'));
    }

    public function testExpandRendersReferencedBlock(): void
    {
        $html = $this->Block->expand('<div data-block="1"></div>');

        $this->assertStringContainsString('block--callout', $html);
        $this->assertStringNotContainsString('data-block', $html);
    }
}
