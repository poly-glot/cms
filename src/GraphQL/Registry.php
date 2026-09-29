<?php

declare(strict_types=1);

namespace App\GraphQL;

use App\GraphQL\Mutation\RootMutation;
use App\GraphQL\Query\RootQuery;
use App\GraphQL\Type\BlockType;
use App\GraphQL\Type\CollectionEntryType;
use App\GraphQL\Type\CollectionType;
use App\GraphQL\Type\CommentType;
use App\GraphQL\Type\Enum\BackedEnumType;
use App\GraphQL\Type\FieldSchemaFieldType;
use App\GraphQL\Type\Input\EntryInput;
use App\GraphQL\Type\Input\PageInput;
use App\GraphQL\Type\Input\PostInput;
use App\GraphQL\Type\MediaRenditionType;
use App\GraphQL\Type\MediaType;
use App\GraphQL\Type\MenuItemType;
use App\GraphQL\Type\MenuType;
use App\GraphQL\Type\PageType;
use App\GraphQL\Type\PostType;
use App\GraphQL\Type\Scalar\DateTimeScalar;
use App\Model\Enum;
use App\Model\Enum\CollectionFieldType;
use App\Model\Enum\CommentStatus;
use App\Model\Enum\PostStatus;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Inflector;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;

final class Registry
{
    private const array FIELD_ALIASES = [
        'createdAt' => 'created',
        'modifiedAt' => 'modified',
        'customFields' => 'data',
    ];

    /** @var array<string, ObjectType> */
    private static array $objects = [];

    /** @var array<string, EnumType> */
    private static array $enums = [];

    /** @var array<string, InputObjectType> */
    private static array $inputs = [];

    private static ?CollectionType $collection = null;

    private static ?CustomScalarType $json = null;

    private static ?DateTimeScalar $dateTime = null;

    private static ?Schema $schema = null;

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            SchemaConfig::create()
                ->setQuery(self::rootQuery())
                ->setMutation(self::rootMutation()),
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function resolveField(mixed $source, array $args, mixed $context, ResolveInfo $info): mixed
    {
        if (!$source instanceof EntityInterface) {
            return Executor::defaultFieldResolver($source, $args, $context, $info);
        }

        return $source->get(self::FIELD_ALIASES[$info->fieldName] ?? Inflector::underscore($info->fieldName));
    }

    public static function rootQuery(): ObjectType
    {
        return self::$objects[RootQuery::class] ??= new RootQuery();
    }

    public static function rootMutation(): ObjectType
    {
        return self::$objects[RootMutation::class] ??= new RootMutation();
    }

    public static function postInput(): InputObjectType
    {
        return self::$inputs[PostInput::class] ??= new PostInput();
    }

    public static function pageInput(): InputObjectType
    {
        return self::$inputs[PageInput::class] ??= new PageInput();
    }

    public static function entryInput(): InputObjectType
    {
        return self::$inputs[EntryInput::class] ??= new EntryInput();
    }

    public static function user(): ObjectType
    {
        return self::$objects['User'] ??= new ObjectType([
            'name' => 'User',
            'description' => 'A content author.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    public static function post(): ObjectType
    {
        return self::$objects[PostType::class] ??= new PostType();
    }

    public static function postConnection(): ObjectType
    {
        return self::connection('PostConnection', self::post());
    }

    public static function page(): ObjectType
    {
        return self::$objects[PageType::class] ??= new PageType();
    }

    public static function pageConnection(): ObjectType
    {
        return self::connection('PageConnection', self::page());
    }

    public static function tag(): ObjectType
    {
        return self::$objects['Tag'] ??= new ObjectType([
            'name' => 'Tag',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'slug' => Type::nonNull(Type::string()),
                'label' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    public static function collection(): CollectionType
    {
        return self::$collection ??= new CollectionType();
    }

    public static function collectionEntry(): ObjectType
    {
        return self::$objects[CollectionEntryType::class] ??= new CollectionEntryType();
    }

    public static function collectionEntryConnection(): ObjectType
    {
        return self::connection('CollectionEntryConnection', self::collectionEntry());
    }

    public static function referenceGroup(): ObjectType
    {
        return self::$objects['ReferenceGroup'] ??= new ObjectType([
            'name' => 'ReferenceGroup',
            'description' => 'Linked entries grouped by the reference field they belong to.',
            'fields' => static fn (): array => [
                'fieldName' => Type::nonNull(Type::string()),
                'entries' => Type::nonNull(Type::listOf(Type::nonNull(self::collectionEntry()))),
            ],
        ]);
    }

    public static function fieldSchemaField(): ObjectType
    {
        return self::$objects[FieldSchemaFieldType::class] ??= new FieldSchemaFieldType();
    }

    public static function fieldOption(): ObjectType
    {
        return self::$objects['FieldOption'] ??= new ObjectType([
            'name' => 'FieldOption',
            'description' => 'A choice for a select field.',
            'fields' => static fn (): array => [
                'value' => Type::nonNull(Type::string()),
                'label' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    public static function block(): ObjectType
    {
        return self::$objects[BlockType::class] ??= new BlockType();
    }

    public static function menu(): ObjectType
    {
        return self::$objects[MenuType::class] ??= new MenuType();
    }

    public static function menuItem(): ObjectType
    {
        return self::$objects[MenuItemType::class] ??= new MenuItemType();
    }

    public static function media(): ObjectType
    {
        return self::$objects[MediaType::class] ??= new MediaType();
    }

    public static function mediaConnection(): ObjectType
    {
        return self::connection('MediaConnection', self::media());
    }

    public static function mediaRendition(): ObjectType
    {
        return self::$objects[MediaRenditionType::class] ??= new MediaRenditionType();
    }

    public static function comment(): ObjectType
    {
        return self::$objects[CommentType::class] ??= new CommentType();
    }

    public static function setting(): ObjectType
    {
        return self::$objects['Setting'] ??= new ObjectType([
            'name' => 'Setting',
            'description' => 'A public site setting key/value pair.',
            'fields' => static fn (): array => [
                'key' => Type::nonNull(Type::string()),
                'value' => Type::string(),
            ],
        ]);
    }

    public static function workspace(): ObjectType
    {
        return self::$objects['Workspace'] ??= new ObjectType([
            'name' => 'Workspace',
            'description' => 'The workspace this endpoint serves.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::nonNull(Type::string()),
                'slug' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    public static function pageInfo(): ObjectType
    {
        return self::$objects['PageInfo'] ??= new ObjectType([
            'name' => 'PageInfo',
            'description' => 'Page-based pagination metadata.',
            'fields' => static fn (): array => [
                'page' => Type::nonNull(Type::int()),
                'perPage' => Type::nonNull(Type::int()),
                'total' => Type::nonNull(Type::int()),
                'hasNextPage' => Type::nonNull(Type::boolean()),
            ],
        ]);
    }

    public static function postStatus(): EnumType
    {
        return self::$enums['PostStatus'] ??= new BackedEnumType('PostStatus', PostStatus::cases());
    }

    public static function pageStatus(): EnumType
    {
        return self::$enums['PageStatus'] ??= new BackedEnumType('PageStatus', PostStatus::cases());
    }

    public static function commentStatus(): EnumType
    {
        return self::$enums['CommentStatus'] ??= new BackedEnumType('CommentStatus', CommentStatus::cases());
    }

    public static function collectionFieldType(): EnumType
    {
        return self::$enums['CollectionFieldType'] ??= new BackedEnumType('CollectionFieldType', CollectionFieldType::cases());
    }

    public static function blockType(): EnumType
    {
        return self::$enums['BlockType'] ??= new BackedEnumType('BlockType', Enum\BlockType::cases());
    }

    public static function menuItemType(): EnumType
    {
        return self::$enums['MenuItemType'] ??= new BackedEnumType('MenuItemType', Enum\MenuItemType::cases());
    }

    public static function json(): CustomScalarType
    {
        return self::$json ??= new CustomScalarType([
            'name' => 'JSON',
            'description' => 'Arbitrary JSON value — object, array, or scalar.',
            'serialize' => static fn (mixed $value): mixed => $value,
        ]);
    }

    public static function dateTime(): DateTimeScalar
    {
        return self::$dateTime ??= new DateTimeScalar();
    }

    private static function connection(string $name, ObjectType $itemType): ObjectType
    {
        return self::$objects[$name] ??= new ObjectType([
            'name' => $name,
            'fields' => static fn (): array => [
                'items' => Type::nonNull(Type::listOf(Type::nonNull($itemType))),
                'pageInfo' => Type::nonNull(self::pageInfo()),
            ],
        ]);
    }
}
