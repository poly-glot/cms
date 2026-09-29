<?php
/**
 * @var App\View\AppView $this
 * @var list<array{name: string, label: string, type: App\Model\Enum\CollectionFieldType, required: bool, options: list<array{value: string, label: string}>}> $subFields
 * @var string $namePrefix
 * @var string $errorPrefix
 * @var array<string, mixed> $values
 * @var array<string, string> $dataErrors
 */
?>
<div class="cms-repeater__row" data-repeater-row>
    <span class="cms-repeater__handle" data-repeater-handle title="Drag to reorder">⠿</span>
    <div class="cms-repeater__fields">
        <?php foreach ($subFields as $sub) {
            $subName = $sub['name'];
            $inputName = $namePrefix . '[' . $subName . ']';
            $value = $values[$subName] ?? '';
            $error = $errorPrefix !== '' ? ($dataErrors[$errorPrefix . '.' . $subName] ?? null) : null;
            ?>
            <div class="cms-field">
                <span class="cms-field__label"><?php echo h($sub['label']); ?><?php if ($sub['required']) { ?> <span class="cms-field__req">*</span><?php } ?></span>
                <?php echo $this->element('admin/collections/field-widget', [
                    'field' => $sub,
                    'inputName' => $inputName,
                    'value' => $value,
                    'required' => $sub['required'],
                ]); ?>
                <?php if ($error !== null) { ?>
                    <p class="cms-field__error"><?php echo h($error); ?></p>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <button type="button" class="cms-repeater__remove" data-repeater-remove title="Remove row">×</button>
</div>
