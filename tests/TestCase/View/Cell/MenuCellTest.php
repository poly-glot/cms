<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Cake\View\View;

final class MenuCellTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.Menus', 'app.MenuItems'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear('menus');
    }

    protected function tearDown(): void
    {
        Cache::clear('menus');
        parent::tearDown();
    }

    private function render(string $slug): string
    {
        return (string) new View()->cell('Menu::display', [$slug]);
    }

    public function testRendersLivePageLinksAndUrlItems(): void
    {
        $html = $this->render('main');

        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('href="/about"', $html);
        $this->assertStringContainsString('Shop', $html);
    }

    public function testExcludesItemsBackedByNonLivePages(): void
    {
        // MenuItems fixture: "Press" targets page 2 (Draft Page, status draft).
        $html = $this->render('main');

        $this->assertStringNotContainsString('Press', $html);
    }

    public function testExternalUrlItemKeepsItsRawUrlAndNewWindowTarget(): void
    {
        $html = $this->render('main');

        $this->assertStringContainsString('https://shop.example.com', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function testCachesResolvedMenuUnderItsSlug(): void
    {
        $this->render('main');

        $this->assertNotFalse(Cache::read('main', 'menus'));
    }

    public function testRendersNothingForUnknownSlug(): void
    {
        $html = $this->render('does-not-exist');

        $this->assertStringNotContainsString('site-nav', $html);
    }
}
