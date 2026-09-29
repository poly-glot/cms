<?php

declare(strict_types=1);

namespace App\Service\Content;

final readonly class ContentImportResult
{
    public function __construct(
        public string $subjectType,
        public string $identifier,
        public ContentImportOutcome $outcome,
        public ?string $error = null,
    ) {
    }
}
