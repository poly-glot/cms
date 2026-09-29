<?php
/**
 * @var App\View\AppView $this
 * @var list<array<string, mixed>> $schema
 * @var list<App\Model\Enum\CollectionFieldType> $fieldTypes
 * @var array<string, int> $fieldsInUse
 * @var list<string> $errorField
 */
$typeMeta = array_map(static fn (App\Model\Enum\CollectionFieldType $type): array => [
    'value' => $type->value,
    'label' => $type->label(),
    'structured' => $type->isStructured(),
    'hasOptions' => $type->hasOptions(),
], $fieldTypes);
?>
<div class="cms-schema-builder"
     data-schema-builder
     data-schema="<?php echo h((string) json_encode($schema)); ?>"
     data-field-types="<?php echo h((string) json_encode($typeMeta)); ?>"
     data-fields-in-use="<?php echo h((string) json_encode($fieldsInUse)); ?>">
    <header class="cms-schema-builder__head">
        <h2 class="cms-schema-builder__title">Fields</h2>
        <button type="button" class="cms-btn cms-btn--primary" data-add-field>+ Add field</button>
    </header>

    <?php if ($errorField !== []) { ?>
        <p class="cms-field__error"><?php echo h(implode(' ', $errorField)); ?></p>
    <?php } ?>

    <div class="cms-schema-builder__list" data-field-list></div>
    <p class="cms-schema-builder__empty" data-empty-hint>No fields yet — add one to define what each entry holds.</p>
</div>
