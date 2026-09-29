<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Post $post
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var array<int, App\Model\Entity\User> $authors
 * @var App\Model\Entity\ContentTypeFieldSchema $fieldSchema
 * @var array<string, string> $dataErrors
 * @var string $submitLabel
 */
$postId = (int) ($post->id ?? 0);
$isExisting = $postId > 0;
$currentStatus = (string) ($post->status ?? 'draft');
$slugPrefix = $workspacePath . '/blog/';
$publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';

$statusLabel = ucfirst($currentStatus);
foreach ($statuses as $status) {
    if ($status->value === $currentStatus) {
        $statusLabel = $status->label();
        break;
    }
}
?>
<?php echo $this->Form->create($post, ['id' => 'post-editor-form']); ?>
<?php echo $this->Form->hidden('title'); ?>
<div class="cms-editor-layout">
    <div class="cms-editor-layout__main">
        <div class="cms-card cms-card--flush">
            <div class="cms-meta-strip">
                <div class="cms-select">
                    <select class="cms-select__control" name="status" aria-label="Post status">
                        <?php foreach ($statuses as $status) { ?>
                            <option value="<?php echo h($status->value); ?>" <?php echo $currentStatus === $status->value ? 'selected' : ''; ?>><?php echo h($status->label()); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="cms-slug-field">
                    <span class="cms-slug-field__prefix"><?php echo h($slugPrefix); ?></span>
                    <?php echo $this->Form->text('slug', [
                        'maxlength' => 160,
                        'pattern' => '[a-z0-9\-]+',
                        'placeholder' => 'auto from title',
                        'aria-label' => 'Slug',
                    ]); ?>
                </div>
                <span class="cms-pill<?php echo $currentStatus === 'draft' ? ' cms-pill--draft' : ''; ?>">
                    <span class="cms-pill__dot<?php echo $currentStatus === 'draft' ? ' cms-pill__dot--draft' : ''; ?>" aria-hidden="true"></span>
                    <?php echo h($statusLabel); ?>
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

        <div class="cms-card">
            <label class="cms-card__field-label" for="post-excerpt">Excerpt</label>
            <?php echo $this->Form->control('excerpt', [
                'id' => 'post-excerpt',
                'type' => 'textarea',
                'rows' => 3,
                'maxlength' => 500,
                'label' => false,
                'class' => 'cms-card__control',
            ]); ?>
            <p class="cms-card__hint">A short summary shown in listings and feeds.</p>
        </div>

        <?php if ($fieldSchema->fields !== []) { ?>
            <section class="cms-card">
                <header class="cms-card__header"><span>Custom fields</span></header>
                <?php echo $this->element('admin/field-schema/fields', [
                    'fields' => $fieldSchema->fields,
                    'values' => $post->data,
                    'dataErrors' => $dataErrors ?? [],
                ]); ?>
            </section>
        <?php } ?>
    </div>

    <aside class="cms-editor-layout__sidebar">
        <section class="cms-card cms-side-card">
            <header class="cms-side-card__header"><h2>Details</h2></header>
            <div class="cms-card__field">
                <label class="cms-card__field-label" for="post-published-at">Publish date</label>
                <input class="cms-card__control" type="datetime-local" id="post-published-at" name="published_at" value="<?php echo h($publishedAt); ?>">
            </div>
            <div class="cms-card__field">
                <label class="cms-card__field-toggle">
                    <input type="hidden" name="comments_enabled" value="0">
                    <input type="checkbox" name="comments_enabled" value="1" <?php echo !empty($post->comments_enabled) ? 'checked' : ''; ?>>
                    Allow comments on this post
                </label>
            </div>
        </section>

        <?php echo $this->element('admin/pages/author-card', ['entity' => $post, 'authors' => $authors ?? [], 'pop' => false]); ?>

        <?php if ($isExisting) { ?>
            <?php echo $this->element('admin/pages/tags-card', ['entity' => $post, 'segment' => 'posts']); ?>
        <?php } ?>
    </aside>
</div>
<?php echo $this->Form->end(); ?>

<footer class="cms-savebar">
    <div class="cms-savebar__hint">
        <span class="cms-savebar__hint-dot" aria-hidden="true"></span>
        Choose a status above, then save.
        <?php if ($isExisting) { ?>
            <?php echo $this->Form->postLink('Delete post', [
                'action' => 'delete', 'id' => $postId, '_method' => 'POST',
            ], ['class' => 'cms-savebar__delete', 'confirm' => 'Delete this post? This cannot be undone.']); ?>
        <?php } ?>
    </div>
    <div class="cms-savebar__actions">
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/posts">Cancel</a>
        <button type="submit" class="cms-btn cms-btn--primary" form="post-editor-form"><?php echo h($submitLabel); ?></button>
    </div>
</footer>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/page-editor.mjs'); ?>"></script>
<?php $this->end(); ?>
