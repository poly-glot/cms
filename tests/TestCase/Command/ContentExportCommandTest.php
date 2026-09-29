<?php

declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\DeletesDirectories;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class ContentExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use DeletesDirectories;

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
        $this->root = TMP . 'tests' . DS . 'content-export-cmd-' . uniqid();
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testExportsAllContentToFiles(): void
    {
        $this->exec('content_export --root=' . $this->root);

        $this->assertExitSuccess();
        $this->assertOutputContains('file(s) written');
        $this->assertFileExists($this->root . '/cabinet/posts/hello.yml');
        $this->assertFileExists($this->root . '/cabinet/pages/about/press.yml');
    }

    public function testExportsOnlyRequestedType(): void
    {
        $this->exec('content_export --type=posts --root=' . $this->root);

        $this->assertExitSuccess();
        $this->assertFileExists($this->root . '/cabinet/posts/hello.yml');
        $this->assertDirectoryDoesNotExist($this->root . '/cabinet/pages');
    }

    public function testFailsForUnknownWorkspace(): void
    {
        $this->exec('content_export --workspace=no-such-workspace --root=' . $this->root);

        $this->assertExitError();
        $this->assertErrorContains('Unknown workspace');
    }
}
