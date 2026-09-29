<?php

declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\DeletesDirectories;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;

final class ContentImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use DeletesDirectories;
    use LocatorAwareTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Posts',
        'app.Pages',
        'app.Collections',
        'app.CollectionEntries',
        'app.CollectionEntryReferences',
        'app.Tags',
        'app.Taggables',
        'app.ContentImports',
        'app.ContentTypeFieldSchemas',
    ];

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = TMP . 'tests' . DS . 'content-import-cmd-' . uniqid();
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testImportsPostsAndReportsCreated(): void
    {
        $this->writePost('welcome', 'Welcome');

        $this->exec('content_import --type=posts --root=' . $this->root);

        $this->assertExitSuccess();
        $this->assertOutputContains('created');
        $this->assertOutputContains('Posts welcome');
        $this->assertNotNull($this->fetchTable('Posts')->find()->where(['Posts.slug' => 'welcome'])->first());
    }

    public function testSecondRunSkipsEverything(): void
    {
        $this->writePost('welcome', 'Welcome');

        $this->exec('content_import --type=posts --root=' . $this->root);
        $this->exec('content_import --type=posts --root=' . $this->root);

        $this->assertExitSuccess();
        $this->assertOutputContains('0 created, 0 updated, 1 skipped, 0 failed');
    }

    public function testForceReimportsReportsUpdated(): void
    {
        $this->writePost('welcome', 'Welcome');

        $this->exec('content_import --type=posts --root=' . $this->root);
        $this->exec('content_import --type=posts --root=' . $this->root . ' --force');

        $this->assertExitSuccess();
        $this->assertOutputContains('updated');
    }

    public function testUnknownAuthorExitsWithError(): void
    {
        $this->writePost('orphan', 'Orphan', 'ghost@nowhere.test');

        $this->exec('content_import --type=posts --root=' . $this->root);

        $this->assertExitError();
        $this->assertOutputContains('failed');
    }

    public function testFailsForUnknownWorkspace(): void
    {
        $this->exec('content_import --workspace=no-such-workspace --root=' . $this->root);

        $this->assertExitError();
        $this->assertErrorContains('Unknown workspace');
    }

    private function writePost(string $slug, string $title, string $author = 'admin@cabinet.local'): void
    {
        $yaml = <<<YAML
            title: {$title}
            slug: {$slug}
            status: draft
            excerpt: null
            body: '<p>Hello.</p>'
            published_at: null
            comments_enabled: false
            author: {$author}
            tags: []
            data: []
            YAML;

        $directory = $this->root . '/cabinet/posts';
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }
        file_put_contents($directory . '/' . $slug . '.yml', $yaml);
    }
}
