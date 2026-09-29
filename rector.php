<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPhpSets(php84: true)
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(50)
    ->withCodeQualityLevel(50)
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        __DIR__ . '/tests/bootstrap.php',
        // CakePHP HTTP exceptions default their $code to the HTTP status (e.g. 404).
        // Passing the previous exception's getCode() overrides that, breaking responses.
        ThrowWithPreviousExceptionRector::class,
        // Policy can*() methods are called by the Authorization plugin with (identity, resource)
        // arguments via duck-typing. Parameters must remain even though unused in the body.
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/src/Policy',
        ],
    ]);
