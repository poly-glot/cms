<?php

declare(strict_types=1);

namespace App\Model\Behavior;

use App\Exception\TenantContextException;
use App\Model\Tenancy\TenantContext;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

final class TenantBehavior extends Behavior
{
    /**
     * @param EventInterface<Table> $event
     * @param SelectQuery<EntityInterface> $query
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $query->where([$this->_table->aliasField('workspace_id') => $workspaceId]);
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, string $operation): void
    {
        $this->stampWorkspace($entity);
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->stampWorkspace($entity);
    }

    private function stampWorkspace(EntityInterface $entity): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $current = $entity->get('workspace_id');

        if ($current === null) {
            $entity->set('workspace_id', $workspaceId);

            return;
        }

        if (!is_int($current) || $current !== $workspaceId) {
            throw new TenantContextException('Refusing to persist a row owned by another workspace.');
        }
    }
}
