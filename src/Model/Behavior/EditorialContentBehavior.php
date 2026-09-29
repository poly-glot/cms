<?php

declare(strict_types=1);

namespace App\Model\Behavior;

use App\Model\Entity\Page;
use App\Model\Entity\Post;
use App\Model\Enum\ContentType;
use App\Model\Enum\PostStatus;
use App\Service\FieldSchema\FieldDataSanitizer;
use App\Service\Page\BodySanitizer;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

final class EditorialContentBehavior extends Behavior
{
    protected array $_defaultConfig = [
        'noun' => 'entries',
        'slugFromTitle' => false,
    ];

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $data
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if ($this->getConfig('slugFromTitle') !== true) {
            return;
        }

        $slug = is_string($data['slug'] ?? null) ? trim($data['slug']) : '';
        $title = is_string($data['title'] ?? null) ? trim($data['title']) : '';

        if ($slug === '' && $title !== '') {
            $data['slug'] = strtolower(Text::slug($title));
        }
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity instanceof Page && !$entity instanceof Post) {
            return;
        }

        if ($entity->isDirty('body') && is_string($entity->body)) {
            $entity->body = new BodySanitizer()->clean($entity->body);
        }

        if ($entity->isDirty('data')) {
            $contentType = $entity instanceof Page ? ContentType::Pages : ContentType::Posts;
            $fields = TableRegistry::getTableLocator()->get('ContentTypeFieldSchemas')->schemaFor($contentType)->fields;
            $entity->data = new FieldDataSanitizer()->clean($fields, $entity->data);
        }
    }

    /**
     * @param EventInterface<Table> $event
     */
    public function buildRules(EventInterface $event, RulesChecker $rules): void
    {
        $noun = $this->getConfig('noun');

        $rules->add(static function (EntityInterface $entity): bool {
            if ($entity->get('status') !== PostStatus::Scheduled->value) {
                return true;
            }

            $publishedAt = $entity->get('published_at');
            if (is_string($publishedAt)) {
                $publishedAt = new DateTime($publishedAt);
            }

            return $publishedAt instanceof DateTime && $publishedAt->greaterThan(DateTime::now());
        }, 'scheduledRequiresFuturePublishedAt', [
            'errorField' => 'published_at',
            'message' => sprintf('Scheduled %s need a published_at date in the future.', is_string($noun) ? $noun : 'entries'),
        ]);
    }

    /**
     * @param SelectQuery<EntityInterface> $query
     * @param array<int, string> $slugs
     * @return SelectQuery<EntityInterface>
     */
    public function findByTags(SelectQuery $query, array $slugs, bool $matchAll = false): SelectQuery
    {
        $wanted = array_values(array_unique(array_filter(
            $slugs,
            static fn (string $slug): bool => $slug !== '',
        )));

        if ($wanted === []) {
            return $query;
        }

        $association = $this->table()->getAssociation('Tags');
        if (!$association instanceof BelongsToMany) {
            return $query;
        }

        $junction = $association->junction();

        $matches = $junction->find()
            ->select([$junction->aliasField('taggable_id')])
            ->innerJoinWith('Tags', static fn (SelectQuery $tags): SelectQuery => $tags->where(['Tags.slug IN' => $wanted]))
            ->where([$junction->aliasField('taggable_type') => $this->table()->getAlias()])
            ->groupBy([$junction->aliasField('taggable_id')]);

        if ($matchAll && count($wanted) > 1) {
            $matches->having([sprintf('COUNT(DISTINCT %s.tag_id) >= %d', $junction->getAlias(), count($wanted))]);
        }

        return $query->where([$this->table()->aliasField('id') . ' IN' => $matches]);
    }

    /**
     * @return array<string, int>
     */
    public function dataUsageByField(string $finder = 'all', mixed ...$args): array
    {
        $rows = $this->table()->find($finder, ...$args)->select(['data'])->all();

        $counts = [];
        foreach ($rows as $row) {
            $data = $row->get('data');
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $name => $value) {
                if (in_array($value, [null, '', []], true)) {
                    continue;
                }
                $counts[(string) $name] = ($counts[(string) $name] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
