<?php
/**
 * @var App\View\AppView $this
 * @var array{type: App\Model\Enum\CollectionFieldType, options: list<array{value: string, label: string}>} $field
 * @var string $inputName
 * @var bool $required
 */
$mediaBase = $workspacePath . '/admin/media/serve';
switch ($field['type']->value) {
    case 'rich_text': ?>
        <textarea data-collections-tiptap name="<?php echo h($inputName); ?>"><?php echo h((string) $value); ?></textarea>
        <?php break;
    case 'textarea': ?>
        <textarea name="<?php echo h($inputName); ?>" rows="6" <?php echo $required ? 'required' : ''; ?>><?php echo h((string) $value); ?></textarea>
        <?php break;
    case 'number': ?>
        <input type="number" step="any" name="<?php echo h($inputName); ?>" value="<?php echo h((string) $value); ?>" <?php echo $required ? 'required' : ''; ?>>
        <?php break;
    case 'date': ?>
        <input type="date" name="<?php echo h($inputName); ?>" value="<?php echo h((string) $value); ?>" <?php echo $required ? 'required' : ''; ?>>
        <?php break;
    case 'datetime': ?>
        <input type="datetime-local" name="<?php echo h($inputName); ?>" value="<?php echo h((string) $value); ?>" <?php echo $required ? 'required' : ''; ?>>
        <?php break;
    case 'boolean': ?>
        <input type="hidden" name="<?php echo h($inputName); ?>" value="0">
        <label class="cms-check"><input type="checkbox" name="<?php echo h($inputName); ?>" value="1" <?php echo !empty($value) ? 'checked' : ''; ?>> Yes</label>
        <?php break;
    case 'select': ?>
        <select name="<?php echo h($inputName); ?>" <?php echo $required ? 'required' : ''; ?>>
            <option value="">— choose —</option>
            <?php foreach ($field['options'] as $option) { ?>
                <option value="<?php echo h($option['value']); ?>" <?php echo (string) $value === $option['value'] ? 'selected' : ''; ?>><?php echo h($option['label']); ?></option>
            <?php } ?>
        </select>
        <?php break;
    case 'media': ?>
        <div class="cms-media-pick" data-collections-media>
            <input type="hidden" name="<?php echo h($inputName); ?>" value="<?php echo h((string) $value); ?>" data-media-id>
            <div class="cms-media-pick__preview" data-media-preview>
                <?php if ($value !== '' && $value !== null) { ?><img src="<?php echo h($mediaBase . '/' . (int) $value); ?>" alt=""><?php } ?>
            </div>
            <div class="cms-media-pick__actions">
                <button type="button" class="cms-btn" data-media-choose>Choose media</button>
                <button type="button" class="cms-btn cms-btn--ghost" data-media-clear <?php echo $value === '' || $value === null ? 'hidden' : ''; ?>>Remove</button>
            </div>
        </div>
        <?php break;
    case 'tags':
        $slugs = is_array($value) ? array_values(array_filter($value, static fn (mixed $slug): bool => is_string($slug) && $slug !== '')) : [];
        ?>
        <div class="cms-tags-field" data-collections-tags data-input-name="<?php echo h($inputName); ?>" data-initial="<?php echo h((string) json_encode($slugs)); ?>">
            <div class="cms-tags-field__pills" data-tags-pills></div>
            <div class="cms-tags-field__inputs" data-tags-inputs hidden></div>
            <div class="cms-tags-field__add">
                <input type="text" class="cms-tags-field__input" placeholder="Add a tag…" autocomplete="off" data-tags-input>
                <div class="cms-tag-suggest" data-tags-suggest hidden></div>
            </div>
        </div>
        <?php break;
    case 'reference':
        $cardinality = ($field['cardinality'] ?? 'one') === 'many' ? 'many' : 'one';
        $ids = $cardinality === 'many'
            ? array_values(array_filter(is_array($value) ? $value : [], static fn (mixed $candidate): bool => is_numeric($candidate)))
            : (is_numeric($value) ? [$value] : []);
        ?>
        <div class="cms-ref"
             data-collections-ref
             data-target="<?php echo h($field['target'] ?? ''); ?>"
             data-cardinality="<?php echo h($cardinality); ?>"
             data-input-name="<?php echo h($inputName); ?>"
             data-initial="<?php echo h((string) json_encode(array_map('intval', $ids))); ?>">
            <div class="cms-ref__pills" data-ref-pills></div>
            <div class="cms-ref__inputs" data-ref-inputs hidden></div>
            <button type="button" class="cms-btn" data-ref-choose>＋ Link entry</button>
        </div>
        <?php break;
    default: ?>
        <input type="text" name="<?php echo h($inputName); ?>" value="<?php echo h((string) $value); ?>" <?php echo $required ? 'required' : ''; ?>>
        <?php
}
