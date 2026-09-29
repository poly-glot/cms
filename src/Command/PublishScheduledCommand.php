<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Page\PagePublisher;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\DateTime;

final class PublishScheduledCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $pages = $this->fetchTable('Pages');
        $due = $pages->find()
            ->where([
                'Pages.status' => 'scheduled',
                'Pages.published_at <=' => DateTime::now(),
            ])
            ->all();

        $publisher = new PagePublisher();
        $count = 0;
        foreach ($due as $page) {
            $publisher->publishScheduled($page);
            ++$count;
        }

        $io->out(sprintf('Published %d scheduled page(s).', $count));

        return self::CODE_SUCCESS;
    }
}
