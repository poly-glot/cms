<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\UnknownWorkspaceException;
use App\Model\Table\WorkspacesTable;
use App\Service\Content\ContentExporter;
use App\Service\Content\ContentKind;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Override;

final class ContentExportCommand extends Command
{
    #[Override]
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('Export a workspace\'s posts, pages, and collection entries to version-controllable YAML files.')
            ->addOption('workspace', [
                'help' => 'Slug of the source workspace.',
                'default' => WorkspacesTable::PRIMARY_SLUG,
            ])
            ->addOption('type', [
                'help' => 'Which content to export.',
                'choices' => ['posts', 'pages', 'entries', 'all'],
                'default' => 'all',
            ])
            ->addOption('collection', [
                'help' => 'Restrict entry export to a single collection slug.',
            ])
            ->addOption('root', [
                'help' => 'Directory the content tree is written under.',
                'default' => 'content',
            ]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $workspaceSlug = (string) $args->getOption('workspace');
        $kinds = ContentKind::fromOption((string) $args->getOption('type'));
        $collection = (string) $args->getOption('collection');
        $root = (string) $args->getOption('root');

        try {
            $written = new ContentExporter()->export($root, $workspaceSlug, $kinds, $collection);
        } catch (UnknownWorkspaceException $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }

        $io->out(sprintf('Exporting content from workspace "%s" into "%s".', $workspaceSlug, $root));
        foreach ($written as $path) {
            $io->out('wrote   ' . $path);
        }

        $io->hr();
        $io->out(sprintf('%d file(s) written.', count($written)));

        return self::CODE_SUCCESS;
    }
}
