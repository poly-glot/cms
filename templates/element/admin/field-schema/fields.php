<?php
/**
 * @var App\View\AppView $this
 * @var list<array{name: string, label: string, type: App\Model\Enum\CollectionFieldType, required: bool, options: list<array{value: string, label: string}>, fields?: list<array{name: string, label: string, type: App\Model\Enum\CollectionFieldType, required: bool, options: list<array{value: string, label: string}>}>}> $fields
 * @var array<string, mixed> $values
 * @var array<string, string> $dataErrors
 * @var string $namePrefix
 */
$namePrefix ??= 'data';
?>
<div class="cms-entry-fields">
    <?php foreach ($fields as $field) {
        $name = $field['name'];
        $value = $values[$name] ?? '';
        $required = $field['required'];
        $error = $dataErrors[$name] ?? null;
        ?>
        <div class="cms-field">
            <span class="cms-field__label"><?php echo h($field['label']); ?><?php if ($required) { ?> <span class="cms-field__req">*</span><?php } ?></span>
            <?php if ($field['type']->value === 'repeater') {
                $rows = is_array($value) ? array_values($value) : [];
                $subFields = $field['fields'] ?? [];
                ?>
                <div class="cms-repeater" data-repeater>
                    <div class="cms-repeater__rows" data-repeater-rows>
                        <?php foreach ($rows as $rowIndex => $rowValues) {
                            echo $this->element('admin/collections/repeater-row', [
                                'subFields' => $subFields,
                                'namePrefix' => $namePrefix . '[' . $name . '][' . $rowIndex . ']',
                                'errorPrefix' => $name . '.' . $rowIndex,
                                'values' => is_array($rowValues) ? $rowValues : [],
                                'dataErrors' => $dataErrors,
                            ]);
                        } ?>
                    </div>
                    <template data-repeater-template><?php echo $this->element('admin/collections/repeater-row', [
                        'subFields' => $subFields,
                        'namePrefix' => $namePrefix . '[' . $name . '][__INDEX__]',
                        'errorPrefix' => '',
                        'values' => [],
                        'dataErrors' => [],
                    ]); ?></template>
                    <button type="button" class="cms-btn cms-btn--ghost" data-repeater-add>+ Add row</button>
                </div>
            <?php } else {
                echo $this->element('admin/collections/field-widget', [
                    'field' => $field,
                    'inputName' => $namePrefix . '[' . $name . ']',
                    'value' => $value,
                    'required' => $required,
                ]);
            } ?>
            <?php if ($error !== null) { ?>
                <p class="cms-field__error"><?php echo h($error); ?></p>
            <?php } ?>
        </div>
    <?php } ?>
</div>
