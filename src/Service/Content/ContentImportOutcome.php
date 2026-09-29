<?php

declare(strict_types=1);

namespace App\Service\Content;

enum ContentImportOutcome: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
