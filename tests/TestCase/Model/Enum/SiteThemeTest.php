<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\SiteTheme;
use Cake\TestSuite\TestCase;

final class SiteThemeTest extends TestCase
{
    public function testHeritageLoadsBaseStylesheetOnly(): void
    {
        $this->assertSame(['heritage'], SiteTheme::Heritage->stylesheets());
    }

    public function testSkinsLayerOverTheHeritageBase(): void
    {
        $this->assertSame(['heritage', 'atelier-dark'], SiteTheme::AtelierDark->stylesheets());
        $this->assertSame(['heritage', 'paper'], SiteTheme::Paper->stylesheets());
    }

    public function testFromValueFallsBackToHeritage(): void
    {
        $this->assertSame(SiteTheme::Paper, SiteTheme::fromValue('paper'));
        $this->assertSame(SiteTheme::Heritage, SiteTheme::fromValue('does-not-exist'));
    }
}
