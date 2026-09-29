<?php

declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class SchemaImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = ['app.Collections', 'app.ContentImports'];

    public function testCreatesCollectionsFromShippedDefinitions(): void
    {
        $this->exec('schema_import');

        $this->assertExitSuccess();
        $this->assertOutputContains('created');
        $this->assertOutputContains('product');
        $this->assertOutputContains('category');

        $collections = $this->fetchTable('Collections');
        $this->assertNotNull($collections->find()->where(['Collections.slug' => 'product'])->first());
        $this->assertNotNull($collections->find()->where(['Collections.slug' => 'category'])->first());
    }

    public function testSecondRunSkipsUnchangedDefinitions(): void
    {
        $this->exec('schema_import');
        $this->exec('schema_import');

        $this->assertExitSuccess();
        $this->assertOutputContains('skipped');
        $this->assertOutputContains('2 skipped');
    }

    public function testForceReimportsUnchangedDefinitions(): void
    {
        $this->exec('schema_import');
        $this->exec('schema_import --force');

        $this->assertExitSuccess();
        $this->assertOutputContains('updated');
    }

    public function testImportsIntoNamedWorkspace(): void
    {
        $this->exec('schema_import --workspace=atelier');

        $this->assertExitSuccess();

        $collections = $this->fetchTable('Collections');
        $this->assertNull($collections->find()->where(['Collections.slug' => 'product'])->first());

        $productExistsInAtelier = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            static fn (): bool => $collections->find()->where(['Collections.slug' => 'product'])->count() === 1,
            workspaceSlug: 'atelier',
        );
        $this->assertTrue($productExistsInAtelier);
    }

    public function testFailsForUnknownWorkspace(): void
    {
        $this->exec('schema_import --workspace=no-such-workspace');

        $this->assertExitError();
        $this->assertErrorContains('Unknown workspace');
    }
}
