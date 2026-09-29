<?php

declare(strict_types=1);

namespace App\Test;

use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class TenantTestExtension implements Extension, PreparationStartedSubscriber
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber($this);
    }

    public function notify(PreparationStarted $event): void
    {
        TenantContext::instance()->activate(1, UserRole::Admin, 'cabinet');
        $this->ensureDefaultWorkspaces();
    }

    private function ensureDefaultWorkspaces(): void
    {
        $connection = ConnectionManager::get('test');
        if (!$connection instanceof Connection) {
            return;
        }

        $connection->execute(
            "INSERT IGNORE INTO workspaces (id, name, slug, created, modified) VALUES
             (1, 'Cabinet', 'cabinet', NOW(), NOW()),
             (2, 'Atelier', 'atelier', NOW(), NOW())",
        );
    }
}
