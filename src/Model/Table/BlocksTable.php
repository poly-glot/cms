<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Block;
use App\Model\Enum\BlockType;
use App\Model\Validation\SafeUrl;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Block>
 */
final class BlocksTable extends Table
{
    private const array ALLOWED_VALUES = [
        'variant' => ['info', 'warning', 'success', 'note'],
        'style' => ['primary', 'secondary'],
        'rendition' => ['large', 'medium', 'small', 'thumb'],
    ];

    /** @var array<string, list<string>> Allowed (required + optional) data keys per type. */
    private const array ALLOWED_KEYS = [
        'callout' => ['variant', 'body'],
        'quote' => ['text', 'attribution'],
        'statistic' => ['value', 'label', 'caption'],
        'cta' => ['label', 'url', 'style'],
        'image' => ['media_id', 'alt', 'caption', 'rendition'],
        'file' => ['media_id', 'label'],
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Authors', [
            'className' => 'Users',
            'joinType' => 'INNER',
        ]);
        $this->belongsToMany('Pages', ['through' => 'PageBlocks']);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('block_type', 'create')
            ->enum('block_type', BlockType::class, 'Unknown block type.');

        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 120);

        $validator
            ->requirePresence('data', 'create')
            ->add('data', 'validForType', [
                'rule' => function (mixed $value, array $context): bool {
                    $input = $context['data'] ?? [];
                    $blockType = is_array($input) ? ($input['block_type'] ?? null) : null;

                    return $this->isValidData($blockType, $value);
                },
                'message' => 'Block data is invalid for this block type.',
            ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['author_id'], 'Authors'), ['errorField' => 'author_id']);

        $rules->add(
            static function (Block $block): bool {
                $type = BlockType::tryFrom((string) $block->block_type);
                if ($type === null || !$type->referencesMedia()) {
                    return true;
                }
                $mediaId = $block->data['media_id'] ?? null;
                if (!is_numeric($mediaId)) {
                    return false;
                }

                return TableRegistry::getTableLocator()->get('Media')->exists(['id' => (int) $mediaId]);
            },
            'mediaExists',
            ['errorField' => 'data', 'message' => 'The referenced media item does not exist.'],
        );

        $rules->addDelete($rules->isNotLinkedTo('Pages'), 'notInUse', [
            'errorField' => 'name',
            'message' => 'This block is in use on one or more pages. Remove it there before deleting.',
        ]);

        return $rules;
    }

    /**
     * @param SelectQuery<Block> $query
     * @return SelectQuery<Block>
     */
    public function findForLibrary(SelectQuery $query, ?string $blockType = null): SelectQuery
    {
        $query->contain(['Authors'])->orderByDesc('Blocks.created');
        if ($blockType !== null && $blockType !== '') {
            $query->where(['Blocks.block_type' => $blockType]);
        }

        return $query;
    }

    /**
     * @param SelectQuery<Block> $query
     * @return SelectQuery<Block>
     */
    public function findForPicker(SelectQuery $query): SelectQuery
    {
        return $query
            ->select(['Blocks.id', 'Blocks.block_type', 'Blocks.name'])
            ->orderBy(['Blocks.block_type' => 'ASC', 'Blocks.name' => 'ASC']);
    }

    private function isValidData(mixed $blockType, mixed $data): bool
    {
        if (!is_string($blockType) || !is_array($data)) {
            return false;
        }
        $type = BlockType::tryFrom($blockType);
        if ($type === null) {
            return false;
        }

        $allowed = self::ALLOWED_KEYS[$blockType];
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowed, true)) {
                return false;
            }
        }

        foreach ($type->requiredKeys() as $required) {
            $present = $data[$required] ?? null;
            if ($present === null || $present === '') {
                return false;
            }
        }

        foreach (self::ALLOWED_VALUES as $key => $values) {
            $value = $data[$key] ?? null;
            if ($value !== null && !in_array($value, $values, true)) {
                return false;
            }
        }

        if ($type === BlockType::Cta) {
            $url = $data['url'] ?? null;
            if (!is_string($url) || !$this->isValidUrl($url)) {
                return false;
            }
        }

        if (!$type->referencesMedia()) {
            return true;
        }

        return $this->isPositiveIntLike($data['media_id'] ?? null);
    }

    private function isValidUrl(string $url): bool
    {
        $isWebUrl = in_array(strtolower((string) parse_url($url, \PHP_URL_SCHEME)), ['http', 'https'], true);

        return SafeUrl::check($url) && (!$isWebUrl || filter_var($url, \FILTER_VALIDATE_URL) !== false);
    }

    private function isPositiveIntLike(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        return is_string($value) && ctype_digit($value) && (int) $value > 0;
    }
}
