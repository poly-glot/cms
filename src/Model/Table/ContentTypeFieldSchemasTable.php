<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ContentTypeFieldSchema;
use App\Model\Enum\ContentType;
use App\Service\FieldSchema\SchemaDefinitionValidator;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, ContentTypeFieldSchema>
 */
final class ContentTypeFieldSchemasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');
    }

    public function schemaFor(ContentType $type): ContentTypeFieldSchema
    {
        $schema = $this->find()
            ->where(['ContentTypeFieldSchemas.subject_type' => $type->value])
            ->first();

        if ($schema instanceof ContentTypeFieldSchema) {
            return $schema;
        }

        $empty = $this->newEmptyEntity();
        $empty->subject_type = $type->value;
        $empty->field_schema = [];

        return $empty;
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator->add('field_schema', 'shape', [
            'rule' => static function (mixed $value, array $context): bool|string {
                /** @var array{data?: array<string, mixed>} $context */
                $subject = $context['data']['subject_type'] ?? null;
                $type = is_string($subject) ? ContentType::tryFrom($subject) : null;
                if ($type === null) {
                    return 'Unknown content type.';
                }

                return new SchemaDefinitionValidator($type->reservedFieldNames(), $type->allowedFieldTypes())->validate($value);
            },
        ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->isUnique(['workspace_id', 'subject_type'], 'A field schema already exists for this content type.'),
            ['errorField' => 'subject_type'],
        );

        return $rules;
    }
}
