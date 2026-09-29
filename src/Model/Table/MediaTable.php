<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Media;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Media>
 */
final class MediaTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Uploaders', [
            'className' => 'Users',
            'foreignKey' => 'uploaded_by',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('Renditions', [
            'className' => 'MediaRenditions',
            'dependent' => true,
        ]);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 255);

        $validator
            ->requirePresence('filename', 'create')
            ->notEmptyString('filename')
            ->maxLength('filename', 36);

        $validator
            ->requirePresence('mime', 'create')
            ->notEmptyString('mime')
            ->maxLength('mime', 120);

        $validator
            ->requirePresence('size', 'create')
            ->integer('size');

        $validator
            ->requirePresence('uploaded_by', 'create')
            ->integer('uploaded_by');

        $validator->maxLength('alt', 500);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['filename', 'workspace_id']), ['errorField' => 'filename']);
        $rules->add($rules->existsIn(['uploaded_by'], 'Uploaders'), ['errorField' => 'uploaded_by']);

        return $rules;
    }

    /**
     * Filters by the entity's virtual `kind`, which is derived from the mime
     * prefix (there is no kind column). `document` is the complement of the
     * media prefixes.
     *
     * @param SelectQuery<Media> $query
     * @return SelectQuery<Media>
     */
    public function findOfKind(SelectQuery $query, string $kind): SelectQuery
    {
        $prefixes = ['image' => 'image/%', 'audio' => 'audio/%', 'video' => 'video/%'];

        if (isset($prefixes[$kind])) {
            return $query->where(['Media.mime LIKE' => $prefixes[$kind]]);
        }

        if ($kind !== 'document') {
            return $query;
        }

        return $query->where(static function (QueryExpression $exp) use ($prefixes): QueryExpression {
            foreach ($prefixes as $prefix) {
                $exp->notLike('Media.mime', $prefix);
            }

            return $exp;
        });
    }

    /**
     * @param SelectQuery<Media> $query
     * @return SelectQuery<Media>
     */
    public function findSearch(SelectQuery $query, string $keyword): SelectQuery
    {
        $term = trim($keyword);
        if ($term === '') {
            return $query;
        }

        return $query->where(['Media.name LIKE' => '%' . addcslashes($term, '%_\\') . '%']);
    }
}
