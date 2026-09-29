<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages', 'app.PageRevisions', 'app.Media', 'app.Blocks'];

    public function testLivePageRenders(): void
    {
        $this->get('/cabinet/about');

        $this->assertResponseOk();
        $this->assertResponseContains('Made by hand, since 1992.');
    }

    public function testDraftPageReturns404(): void
    {
        $this->get('/cabinet/draft-page');

        $this->assertResponseCode(404);
    }

    public function testUnknownSlugReturns404(): void
    {
        $this->get('/cabinet/no-such-page');

        $this->assertResponseCode(404);
    }

    public function testNestedPathResolves(): void
    {
        $this->get('/cabinet/about/press');

        $this->assertResponseOk();
        $this->assertResponseContains('Press materials.');
    }

    public function testMissingSegmentReturns404(): void
    {
        $this->get('/cabinet/about/no-such-child');

        $this->assertResponseCode(404);
    }

    public function testRootStillResolves(): void
    {
        $this->get('/cabinet/about');

        $this->assertResponseOk();
        $this->assertResponseContains('Made by hand, since 1992.');
    }

    public function testLivePageExpandsBlockReferences(): void
    {
        $this->fetchTable('Pages')
            ->updateAll(['body' => '<p>Intro.</p><div data-block="1"></div>'], ['id' => 1]);

        $this->get('/cabinet/about');

        $this->assertResponseOk();
        $this->assertResponseContains('block--callout');
        $this->assertResponseNotContains('data-block');
    }
}
