<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Collection $collection
 * @var App\Model\Entity\CollectionEntry $entry
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var array<string, string> $dataErrors
 * @var string $action
 * @var string $submitLabel
 */
$values = $entry->data ?? [];
$dataErrors = $dataErrors ?? [];
$entryId = (int) ($entry->id ?? 0);
$isExisting = $entryId > 0;
$entriesUrl = $workspacePath . '/admin/collections/' . (int) $collection->id . '/entries';
?>
<?php echo $this->Form->create(null, ['url' => $action, 'id' => 'collection-entry-form', 'class' => 'cms-entry-form']); ?>

    <div class="cms-editor-layout">
        <div class="cms-editor-layout__main">
            <div class="cms-card cms-card--flush">
                <input type="hidden" name="title" value="<?php echo h($entry->title ?? ''); ?>">
                <div class="cms-meta-strip cms-meta-strip--slug">
                    <div class="cms-slug-field">
                        <span class="cms-slug-field__prefix"><?php echo h($collection->slug); ?>/</span>
                        <input type="text" name="slug" value="<?php echo h($entry->slug ?? ''); ?>" maxlength="160" pattern="[a-z0-9\-]+" placeholder="entry-slug" aria-label="Slug">
                    </div>
                </div>
                <?php if ($entry->getError('slug') !== []) { ?>
                    <p class="cms-field__error cms-meta-strip__error"><?php echo h(implode(' ', $entry->getError('slug'))); ?></p>
                <?php } ?>

                <?php echo $this->element('admin/field-schema/fields', [
                    'fields' => $collection->fields,
                    'values' => $values,
                    'dataErrors' => $dataErrors,
                ]); ?>
            </div>
        </div>

        <aside class="cms-editor-layout__sidebar">
            <section class="cms-card cms-side-card">
                <header class="cms-side-card__header"><h2>Publish</h2></header>
                <div class="cms-card__field">
                    <label class="cms-card__field-label" for="entry-status">Status</label>
                    <select class="cms-card__control" name="status" id="entry-status">
                        <?php foreach ($statuses as $status) { ?>
                            <option value="<?php echo h($status->value); ?>" <?php echo ($entry->status ?? 'draft') === $status->value ? 'selected' : ''; ?>><?php echo h($status->label()); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="cms-card__field">
                    <label class="cms-card__field-label" for="entry-published">Published at</label>
                    <input class="cms-card__control" type="datetime-local" name="published_at" id="entry-published" value="<?php echo h($entry->published_at?->format('Y-m-d\TH:i') ?? ''); ?>">
                    <?php if ($entry->getError('published_at') !== []) { ?>
                        <p class="cms-field__error"><?php echo h(implode(' ', $entry->getError('published_at'))); ?></p>
                    <?php } ?>
                </div>
                <div class="cms-card__field">
                    <label class="cms-card__field-toggle">
                        <input type="hidden" name="comments_enabled" value="0">
                        <input type="checkbox" name="comments_enabled" value="1" <?php echo !empty($entry->comments_enabled) ? 'checked' : ''; ?>> Allow comments
                    </label>
                </div>
            </section>
        </aside>
    </div>
<?php echo $this->Form->end(); ?>

<footer class="cms-savebar">
    <div class="cms-savebar__hint">
        <?php if ($isExisting) { ?>
            <?php echo $this->Form->postLink('Delete entry', $entriesUrl . '/delete/' . $entryId, [
                'class' => 'cms-savebar__delete',
                'confirm' => 'Delete this entry? This cannot be undone.',
            ]); ?>
        <?php } ?>
    </div>
    <div class="cms-savebar__actions">
        <a class="cms-btn" href="<?php echo h($entriesUrl); ?>">Cancel</a>
        <button type="submit" class="cms-btn cms-btn--primary" form="collection-entry-form"><?php echo h($submitLabel); ?></button>
    </div>
</footer>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/collections-editor.mjs'); ?>"></script>
<?php $this->end(); ?>
