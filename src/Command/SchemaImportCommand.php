<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\UnknownWorkspaceException;
use App\Model\Table\WorkspacesTable;
use App\Service\Content\ContentImporter;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Override;

final class SchemaImportCommand extends ContentImportCommand
{
    #[Override]
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('Import code-defined collection schemas from config/collections/*.yml into a workspace.')
            ->addOption('workspace', [
                'help' => 'Slug of the target workspace.',
                'default' => WorkspacesTable::PRIMARY_SLUG,
            ])
            ->addOption('force', [
                'boolean' => true,
                'help' => 'Re-import every definition, ignoring the content-hash gate.',
            ]);
    }

    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $workspaceSlug = (string) $args->getOption('workspace');
        $force = $args->getOption('force') === true;

        try {
            $results = new ContentImporter()->importSchemas(CONFIG . 'collections', $workspaceSlug, $force);
        } catch (UnknownWorkspaceException $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }

        return $this->summarise($results, sprintf('Importing collection schemas into workspace "%s".', $workspaceSlug), $io);
    }
}
