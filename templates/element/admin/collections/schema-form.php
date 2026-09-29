<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Collection $collection
 * @var list<App\Model\Enum\CollectionFieldType> $fieldTypes
 * @var array<string, int> $fieldsInUse
 * @var string $action
 * @var string $submitLabel
 */
$collectionId = (int) ($collection->id ?? 0);
$isExisting = $collectionId > 0;
?>
<?php echo $this->Form->create(null, ['url' => $action, 'id' => 'collection-schema-form', 'class' => 'cms-schema-form', 'data-schema-form' => true]); ?>
    <input type="hidden" name="name" value="<?php echo h($collection->name ?? ''); ?>">

    <div class="cms-editor-layout">
        <div class="cms-editor-layout__main">
            <div class="cms-card cms-card--flush">
                <div class="cms-meta-strip cms-meta-strip--slug">
                    <div class="cms-slug-field">
                        <span class="cms-slug-field__prefix"><?php echo h($workspacePath); ?>/c/</span>
                        <input type="text" name="slug" value="<?php echo h($collection->slug ?? ''); ?>" maxlength="80" pattern="[a-z0-9\-]+" placeholder="collection-slug" aria-label="Slug">
                    </div>
                </div>
                <?php if ($collection->getError('name') !== []) { ?>
                    <p class="cms-field__error cms-meta-strip__error"><?php echo h(implode(' ', $collection->getError('name'))); ?></p>
                <?php } ?>
                <?php if ($collection->getError('slug') !== []) { ?>
                    <p class="cms-field__error cms-meta-strip__error"><?php echo h(implode(' ', $collection->getError('slug'))); ?></p>
                <?php } ?>

                <?php echo $this->element('admin/field-schema/builder', [
                    'schema' => $collection->field_schema ?? [],
                    'fieldTypes' => $fieldTypes,
                    'fieldsInUse' => $fieldsInUse,
                    'errorField' => $collection->getError('field_schema'),
                ]); ?>
            </div>
        </div>

        <aside class="cms-editor-layout__sidebar">
            <section class="cms-card cms-side-card">
                <header class="cms-side-card__header"><h2>Details</h2></header>
                <div class="cms-card__field">
                    <label class="cms-card__field-label" for="collection-description">Description</label>
                    <textarea class="cms-card__control" name="description" id="collection-description" maxlength="500" rows="3" placeholder="What is this content type for?"><?php echo h($collection->description ?? ''); ?></textarea>
                </div>
            </section>
        </aside>
    </div>

    <input type="hidden" name="field_schema_json" data-schema-input value="">
<?php echo $this->Form->end(); ?>

<footer class="cms-savebar">
    <div class="cms-savebar__hint">
        <?php if ($isExisting) { ?>
            <?php echo $this->Form->postLink('Delete collection', $workspacePath . '/admin/collections/delete/' . $collectionId, [
                'class' => 'cms-savebar__delete',
                'confirm' => 'Delete this collection and all its entries? This cannot be undone.',
            ]); ?>
        <?php } ?>
    </div>
    <div class="cms-savebar__actions">
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/collections">Cancel</a>
        <button type="submit" class="cms-btn cms-btn--primary" form="collection-schema-form"><?php echo h($submitLabel); ?></button>
    </div>
</footer>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/schema-builder.mjs'); ?>"></script>
<?php $this->end(); ?>
