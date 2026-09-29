<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\UnknownWorkspaceException;
use App\Model\Table\WorkspacesTable;
use App\Service\Content\ContentImporter;
use App\Service\Content\ContentImportOutcome;
use App\Service\Content\ContentImportResult;
use App\Service\Content\ContentKind;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Override;

class ContentImportCommand extends Command
{
    #[Override]
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('Import posts, pages, and collection entries from YAML files into a workspace (hash-gated create-or-replace).')
            ->addOption('workspace', [
                'help' => 'Slug of the target workspace.',
                'default' => WorkspacesTable::PRIMARY_SLUG,
            ])
            ->addOption('type', [
                'help' => 'Which content to import.',
                'choices' => ['posts', 'pages', 'entries', 'all'],
                'default' => 'all',
            ])
            ->addOption('collection', [
                'help' => 'Restrict entry import to a single collection slug.',
            ])
            ->addOption('root', [
                'help' => 'Directory the content tree is read from.',
                'default' => 'content',
            ])
            ->addOption('force', [
                'boolean' => true,
                'help' => 'Re-import every file, ignoring the content-hash gate.',
            ]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $workspaceSlug = (string) $args->getOption('workspace');
        $kinds = ContentKind::fromOption((string) $args->getOption('type'));
        $collection = (string) $args->getOption('collection');
        $root = (string) $args->getOption('root');
        $force = $args->getOption('force') === true;

        try {
            $results = new ContentImporter()->import($root, $workspaceSlug, $kinds, $collection, $force);
        } catch (UnknownWorkspaceException $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }

        return $this->summarise($results, sprintf('Importing content into workspace "%s".', $workspaceSlug), $io);
    }

    /**
     * @param list<ContentImportResult> $results
     */
    protected function summarise(array $results, string $heading, ConsoleIo $io): int
    {
        $io->out($heading);

        if ($results === []) {
            $io->out('No files found.');

            return self::CODE_SUCCESS;
        }

        $counts = [];
        foreach ($results as $result) {
            $counts[$result->outcome->value] = ($counts[$result->outcome->value] ?? 0) + 1;
            $io->out($this->line($result));
        }

        $io->hr();
        $io->out(sprintf(
            '%d created, %d updated, %d skipped, %d failed.',
            $counts[ContentImportOutcome::Created->value] ?? 0,
            $counts[ContentImportOutcome::Updated->value] ?? 0,
            $counts[ContentImportOutcome::Skipped->value] ?? 0,
            $counts[ContentImportOutcome::Failed->value] ?? 0,
        ));

        return ($counts[ContentImportOutcome::Failed->value] ?? 0) > 0 ? self::CODE_ERROR : self::CODE_SUCCESS;
    }

    private function line(ContentImportResult $result): string
    {
        $label = str_pad($result->outcome->value, 8);
        $subject = sprintf('%s %s', $result->subjectType, $result->identifier);

        if ($result->outcome === ContentImportOutcome::Failed) {
            return sprintf('%s%s — %s', $label, $subject, $result->error ?? 'invalid file');
        }

        return sprintf('%s%s', $label, $subject);
    }
}
