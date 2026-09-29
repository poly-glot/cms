<?php

declare(strict_types=1);

namespace App\GraphQL\Mutation;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\GraphQL\Support\UserError;
use App\GraphQL\Support\ValidationError;
use App\Model\Entity\Collection;
use App\Model\Entity\Page;
use App\Model\Enum\CommentStatus;
use App\Model\Enum\ContentType;
use App\Model\Enum\PostStatus;
use App\Service\FieldSchema\FieldDataValidator;
use App\Service\Page\PagePublisher;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use DomainException;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class RootMutation extends ObjectType
{
    use LocatorAwareTrait;

    private const array CUSTOM_FIELD_INPUTS = ['customFields' => true, 'data' => true];

    public function __construct()
    {
        parent::__construct([
            'name' => 'Mutation',
            'fields' => fn (): array => [
                'createPost' => [
                    'type' => Registry::post(),
                    'args' => ['input' => Type::nonNull(Registry::postInput())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->createContent(ContentType::Posts, $args, $context),
                ],
                'updatePost' => [
                    'type' => Registry::post(),
                    'args' => ['id' => Type::nonNull(Type::id()), 'input' => Type::nonNull(Registry::postInput())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->updateContent(ContentType::Posts, $args, $context),
                ],
                'deletePost' => [
                    'type' => Type::boolean(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): bool => $this->deleteRecord('Posts', 'delete', $args, $context),
                ],
                'publishPost' => [
                    'type' => Registry::post(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->publishPost($args, $context),
                ],
                'schedulePost' => [
                    'type' => Registry::post(),
                    'args' => ['id' => Type::nonNull(Type::id()), 'publishedAt' => Type::nonNull(Registry::dateTime())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->schedulePost($args, $context),
                ],
                'createPage' => [
                    'type' => Registry::page(),
                    'args' => ['input' => Type::nonNull(Registry::pageInput())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->createContent(ContentType::Pages, $args, $context),
                ],
                'updatePage' => [
                    'type' => Registry::page(),
                    'args' => ['id' => Type::nonNull(Type::id()), 'input' => Type::nonNull(Registry::pageInput())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->updateContent(ContentType::Pages, $args, $context),
                ],
                'deletePage' => [
                    'type' => Type::boolean(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): bool => $this->deleteRecord('Pages', 'delete', $args, $context),
                ],
                'publishPage' => [
                    'type' => Registry::page(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->publishPage($args, $context),
                ],
                'schedulePage' => [
                    'type' => Registry::page(),
                    'args' => ['id' => Type::nonNull(Type::id()), 'publishedAt' => Type::nonNull(Registry::dateTime())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->schedulePage($args, $context),
                ],
                'createEntry' => [
                    'type' => Registry::collectionEntry(),
                    'args' => ['collection' => Type::nonNull(Type::string()), 'input' => Type::nonNull(Registry::entryInput())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->createEntry($args, $context),
                ],
                'updateEntry' => [
                    'type' => Registry::collectionEntry(),
                    'args' => ['id' => Type::nonNull(Type::id()), 'input' => Type::nonNull(Registry::entryInput())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->updateEntry($args, $context),
                ],
                'deleteEntry' => [
                    'type' => Type::boolean(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): bool => $this->deleteRecord('CollectionEntries', 'delete', $args, $context),
                ],
                'approveComment' => [
                    'type' => Registry::comment(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->moderateComment($args, $context, CommentStatus::Approved),
                ],
                'spamComment' => [
                    'type' => Registry::comment(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->moderateComment($args, $context, CommentStatus::Spam),
                ],
                'archiveComment' => [
                    'type' => Registry::comment(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): EntityInterface => $this->moderateComment($args, $context, CommentStatus::Archived),
                ],
                'deleteComment' => [
                    'type' => Type::boolean(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): bool => $this->deleteRecord('Comments', 'moderate', $args, $context),
                ],
            ],
        ]);
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function createContent(ContentType $type, array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $input = $this->inputArray($args);
        $table = $this->fetchTable($type->value);

        $validation = $this->coerceCustomFields($input['customFields'] ?? [], $context->loaders->contentFieldSchema($type));
        $patch = $this->patchFrom($input);
        $patch['data'] = $validation['data'];

        $content = $table->patchEntity($table->newEmptyEntity(), $patch);
        $content->set('author_id', $context->requireViewerId());
        $context->authorize($content, 'add');
        $this->guardErrors($validation['errors']);
        $table->saveOrFail($content);

        return $content;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function updateContent(ContentType $type, array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $input = $this->inputArray($args);
        $table = $this->fetchTable($type->value);
        $content = $this->getOrFail($table, $args['id'] ?? null);
        $context->authorize($content, 'edit');

        $patch = $this->patchFrom($input);
        $errors = [];
        if (array_key_exists('customFields', $input)) {
            $validation = $this->coerceCustomFields($input['customFields'], $context->loaders->contentFieldSchema($type));
            $patch['data'] = $validation['data'];
            $errors = $validation['errors'];
        }

        $content = $table->patchEntity($content, $patch);
        $this->guardErrors($errors);
        $table->saveOrFail($content);

        return $content;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function deleteRecord(string $tableName, string $action, array $args, GraphqlContext $context): bool
    {
        $context->requireWritable();
        $table = $this->fetchTable($tableName);
        $record = $this->getOrFail($table, $args['id'] ?? null);
        $context->authorize($record, $action);
        $table->deleteOrFail($record);

        return true;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function publishPost(array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $posts = $this->fetchTable('Posts');
        $post = $this->getOrFail($posts, $args['id'] ?? null);
        $context->authorize($post, 'publish');

        $post = $posts->patchEntity($post, ['status' => PostStatus::Live->value, 'published_at' => DateTime::now()]);
        $posts->saveOrFail($post);

        return $post;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function schedulePost(array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $posts = $this->fetchTable('Posts');
        $post = $this->getOrFail($posts, $args['id'] ?? null);
        $context->authorize($post, 'schedule');

        $post = $posts->patchEntity($post, ['status' => PostStatus::Scheduled->value, 'published_at' => $this->dateArg($args, 'publishedAt')]);
        $posts->saveOrFail($post);

        return $post;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function publishPage(array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $page = $this->pageOrFail($args['id'] ?? null);
        $context->authorize($page, 'publish');

        return new PagePublisher()->publish($page, [], authorId: $context->requireViewerId());
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function schedulePage(array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $page = $this->pageOrFail($args['id'] ?? null);
        $context->authorize($page, 'schedule');

        try {
            return new PagePublisher()->schedule($page, [], when: $this->dateArg($args, 'publishedAt'), authorId: $context->requireViewerId());
        } catch (DomainException $exception) {
            throw new ValidationError([['field' => 'published_at', 'rule' => 'future', 'message' => $exception->getMessage()]]);
        }
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function createEntry(array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $input = $this->inputArray($args);
        $collection = $this->collectionBySlug($this->stringArg($args, 'collection'))
            ?? throw new UserError('Unknown collection.');

        $entries = $this->fetchTable('CollectionEntries');
        $entry = $entries->newEmptyEntity();
        $entry->set('collection_id', $collection->id);
        $context->authorize($entry, 'add');

        $validation = $this->coerceCustomFields($input['data'] ?? [], $collection->fields);
        $patch = $this->patchFrom($input);
        $patch['data'] = $validation['data'];
        $patch['collection_id'] = $collection->id;

        $entry = $entries->patchEntity($entry, $patch);
        $entry->set('author_id', $context->requireViewerId());
        $this->authorizeEntryPublish($entry, $context);
        $this->guardErrors($validation['errors']);
        $entries->saveOrFail($entry);

        return $entry;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function updateEntry(array $args, GraphqlContext $context): EntityInterface
    {
        $context->requireWritable();
        $input = $this->inputArray($args);
        $entries = $this->fetchTable('CollectionEntries');
        $entry = $this->getOrFail($entries, $args['id'] ?? null);
        $context->authorize($entry, 'edit');

        $collectionId = $entry->get('collection_id');
        $collection = $this->collectionById(is_numeric($collectionId) ? (int) $collectionId : 0)
            ?? throw new UserError('Unknown collection.');

        $patch = $this->patchFrom($input);
        $errors = [];
        if (array_key_exists('data', $input)) {
            $validation = $this->coerceCustomFields($input['data'], $collection->fields);
            $patch['data'] = $validation['data'];
            $errors = $validation['errors'];
        }

        $entry = $entries->patchEntity($entry, $patch);
        $this->authorizeEntryPublish($entry, $context);
        $this->guardErrors($errors);
        $entries->saveOrFail($entry);

        return $entry;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function moderateComment(array $args, GraphqlContext $context, CommentStatus $status): EntityInterface
    {
        $context->requireWritable();
        $comments = $this->fetchTable('Comments');
        $comment = $this->getOrFail($comments, $args['id'] ?? null);
        $context->authorize($comment, 'moderate');

        $comment->set('status', $status->value);
        $comment->set('is_read', true);
        $comments->saveOrFail($comment);

        return $comment;
    }

    private function authorizeEntryPublish(EntityInterface $entry, GraphqlContext $context): void
    {
        $publishing = in_array($entry->get('status'), [PostStatus::Live->value, PostStatus::Scheduled->value], true);
        if ($publishing && $entry->isDirty('status')) {
            $context->authorize($entry, 'publish');
        }
    }

    private function getOrFail(Table $table, mixed $idArg): EntityInterface
    {
        try {
            return $table->get($this->intId($idArg));
        } catch (RecordNotFoundException) {
            throw new UserError('Not found.');
        }
    }

    private function pageOrFail(mixed $idArg): Page
    {
        $page = $this->getOrFail($this->fetchTable('Pages'), $idArg);

        return $page instanceof Page ? $page : throw new UserError('Not found.');
    }

    private function collectionBySlug(?string $slug): ?Collection
    {
        if ($slug === null) {
            return null;
        }

        $collection = $this->fetchTable('Collections')->findBySlug($slug)->first();

        return $collection instanceof Collection ? $collection : null;
    }

    private function collectionById(int $id): ?Collection
    {
        $collection = $this->fetchTable('Collections')->find()->where(['Collections.id' => $id])->first();

        return $collection instanceof Collection ? $collection : null;
    }

    /**
     * @param array<array-key, mixed> $fields
     * @return array{data: array<string, mixed>, errors: array<string, string>}
     */
    private function coerceCustomFields(mixed $raw, array $fields): array
    {
        return new FieldDataValidator()->validate($fields, is_array($raw) ? $raw : []);
    }

    /**
     * @param array<string, string> $errors
     */
    private function guardErrors(array $errors): void
    {
        if ($errors !== []) {
            throw ValidationError::fromDottedPaths($errors);
        }
    }

    /**
     * @param array<array-key, mixed> $input
     * @return array<string, mixed>
     */
    private function patchFrom(array $input): array
    {
        $patch = [];
        foreach (array_diff_key($input, self::CUSTOM_FIELD_INPUTS) as $key => $value) {
            $patch[Inflector::underscore((string) $key)] = $value;
        }

        if (array_key_exists('parent_id', $patch)) {
            $patch['parent_id'] = is_numeric($patch['parent_id']) ? (int) $patch['parent_id'] : null;
        }

        return $patch;
    }

    /**
     * @param array<array-key, mixed> $args
     * @return array<array-key, mixed>
     */
    private function inputArray(array $args): array
    {
        $input = $args['input'] ?? null;

        return is_array($input) ? $input : [];
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function stringArg(array $args, string $key): ?string
    {
        $value = $args[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function dateArg(array $args, string $key): DateTime
    {
        $value = $args[$key] ?? null;

        return $value instanceof DateTime ? $value : throw new UserError('A valid date is required.');
    }

    private function intId(mixed $value): int
    {
        if (!is_string($value) || !ctype_digit($value)) {
            throw new UserError('A valid id is required.');
        }

        return (int) $value;
    }
}
