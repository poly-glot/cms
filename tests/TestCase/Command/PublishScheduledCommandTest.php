<?php

declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

final class PublishScheduledCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Pages', 'app.PageRevisions'];

    public function testFlipsPastScheduledPageToLive(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->status = 'scheduled';
        $page->published_at = DateTime::now()->subMinutes(5);
        $pages->saveOrFail($page, ['checkRules' => false]);

        $this->exec('pages publish-scheduled');

        $this->assertExitSuccess();
        $this->assertOutputContains('Published 1 scheduled page');

        $refreshed = $pages->get(2);
        $this->assertSame('live', $refreshed->status);
    }

    public function testLeavesFutureScheduledPageAlone(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->status = 'scheduled';
        $page->published_at = DateTime::now()->addDays(7);
        $pages->saveOrFail($page, ['checkRules' => false]);

        $this->exec('pages publish-scheduled');

        $this->assertExitSuccess();
        $this->assertOutputContains('Published 0 scheduled page');

        $refreshed = $pages->get(2);
        $this->assertSame('scheduled', $refreshed->status);
    }
}
