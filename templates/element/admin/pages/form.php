<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Page $page
 * @var array<int, string> $treeList
 * @var array<int, string> $authors
 * @var array<App\Model\Entity\PageRevision> $revisions
 * @var App\Model\Entity\ContentTypeFieldSchema $fieldSchema
 * @var array<string, string> $dataErrors
 * @var string $submitLabel
 * @var string|null $parentPath
 */
$pageId = (int) ($page->id ?? 0);
$isExisting = $pageId > 0;
$statuses = ['draft' => 'Draft', 'live' => 'Published', 'scheduled' => 'Scheduled'];
$currentStatus = (string) ($page->status ?? 'draft');
$slugPrefix = $workspacePath . '/' . (isset($parentPath) ? $parentPath . '/' : '');
?>
<?php echo $this->Form->create($page, ['id' => 'page-editor-form']); ?>
<?php echo $this->Form->hidden('title'); ?>
<div class="cms-editor-layout">
    <div class="cms-editor-layout__main">
        <div class="cms-card cms-card--flush">
            <div class="cms-meta-strip">
                <div class="cms-select">
                    <select class="cms-select__control" name="status" aria-label="Page status">
                        <?php foreach ($statuses as $value => $label) { ?>
                            <option value="<?php echo h($value); ?>" <?php echo $currentStatus === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="cms-slug-field">
                    <span class="cms-slug-field__prefix"><?php echo h($slugPrefix); ?></span>
                    <?php echo $this->Form->text('slug', [
                        'required' => true,
                        'maxlength' => 160,
                        'pattern' => '[a-z0-9\-]+',
                        'placeholder' => 'page-slug',
                        'aria-label' => 'Slug',
                    ]); ?>
                </div>
                <span class="cms-autosave <?php echo $isExisting ? 'cms-autosave--saved' : 'cms-autosave--idle'; ?>" data-autosave>
                    <span class="cms-autosave__dot" aria-hidden="true"></span>
                    <span data-saved-indicator><?php echo $isExisting ? 'Auto-saved' : 'Not saved yet'; ?></span>
                </span>
            </div>
            <?php echo $this->Form->control('body', [
                'type' => 'textarea',
                'data-tiptap' => '1',
                'rows' => 16,
                'escape' => true,
                'label' => false,
            ]); ?>
        </div>

        <?php if ($fieldSchema->fields !== []) { ?>
            <section class="cms-card">
                <header class="cms-card__header"><span>Custom fields</span></header>
                <?php echo $this->element('admin/field-schema/fields', [
                    'fields' => $fieldSchema->fields,
                    'values' => $page->data,
                    'dataErrors' => $dataErrors ?? [],
                ]); ?>
            </section>
        <?php } ?>
    </div>

    <aside class="cms-editor-layout__sidebar" data-pages-sidebar data-page-id="<?php echo $pageId; ?>">
        <?php echo $this->element('admin/pages/details-card', ['page' => $page, 'treeList' => $treeList ?? []]); ?>
        <?php echo $this->element('admin/pages/author-card', ['entity' => $page, 'authors' => $authors ?? [], 'pop' => true]); ?>
        <?php echo $this->element('admin/pages/tags-card', ['entity' => $page, 'segment' => 'pages']); ?>
        <?php echo $this->element('admin/pages/revisions-card', ['page' => $page, 'revisions' => $revisions ?? []]); ?>
    </aside>
</div>

<?php echo $this->Form->end(); ?>

<footer class="cms-savebar">
    <div class="cms-savebar__hint">
        <?php if ($isExisting) { ?>
            <span class="cms-savebar__hint-dot" aria-hidden="true"></span>
            <span>Drafts save automatically · publish to go live</span>
            <?php echo $this->Form->postLink('Delete page', [
                'action' => 'delete', 'id' => $pageId, '_method' => 'POST',
            ], ['class' => 'cms-savebar__delete', 'confirm' => 'Delete this page? This cannot be undone.']); ?>
        <?php } ?>
    </div>
    <div class="cms-savebar__actions">
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/pages">Cancel</a>
        <?php if ($isExisting) { ?>
            <?php echo $this->Form->postButton('Save Draft', ['action' => 'saveDraft', 'id' => $pageId, '_method' => 'POST'], [
                'class' => 'cms-btn',
                'form' => ['id' => 'page-savedraft-form'],
            ]); ?>
            <details class="cms-publish">
                <summary class="cms-btn cms-btn--primary">Publish ▾</summary>
                <div class="cms-publish__menu">
                    <?php echo $this->Form->postButton('Publish now', ['action' => 'publish', 'id' => $pageId, '_method' => 'POST'], [
                        'class' => 'cms-btn cms-btn--primary',
                        'form' => ['id' => 'page-publish-form'],
                    ]); ?>
                    <?php echo $this->Form->create(null, ['url' => ['action' => 'schedule', 'id' => $pageId, '_method' => 'POST']]); ?>
                        <label>Schedule for <input type="datetime-local" name="published_at" required></label>
                        <button type="submit" class="cms-btn">Schedule</button>
                    <?php echo $this->Form->end(); ?>
                </div>
            </details>
        <?php } else { ?>
            <button type="submit" class="cms-btn cms-btn--primary" form="page-editor-form"><?php echo h($submitLabel ?? 'Create page'); ?></button>
        <?php } ?>
    </div>
</footer>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/page-editor.mjs'); ?>"></script>
<?php $this->end(); ?>
