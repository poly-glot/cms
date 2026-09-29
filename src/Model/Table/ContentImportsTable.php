<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ContentImport;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, ContentImport>
 */
final class ContentImportsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->setDisplayField('slug');
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('subject_type', 'create')
            ->notEmptyString('subject_type')
            ->maxLength('subject_type', 120);

        $validator
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->maxLength('slug', 190);

        $validator
            ->requirePresence('hash', 'create')
            ->notEmptyString('hash')
            ->maxLength('hash', 64);

        $validator
            ->requirePresence('imported_at', 'create')
            ->notEmptyDateTime('imported_at');

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['workspace_id', 'subject_type', 'slug']), ['errorField' => 'slug']);

        return $rules;
    }

    public function hashFor(string $subjectType, string $slug): ?string
    {
        $record = $this->find()
            ->where(['ContentImports.subject_type' => $subjectType, 'ContentImports.slug' => $slug])
            ->first();

        return $record?->hash;
    }

    public function record(string $subjectType, string $slug, string $hash): void
    {
        $record = $this->find()
            ->where(['ContentImports.subject_type' => $subjectType, 'ContentImports.slug' => $slug])
            ->first()
            ?? $this->newEmptyEntity();

        $this->patchEntity($record, [
            'subject_type' => $subjectType,
            'slug' => $slug,
            'hash' => $hash,
            'imported_at' => DateTime::now(),
        ]);

        $this->saveOrFail($record);
    }
}
