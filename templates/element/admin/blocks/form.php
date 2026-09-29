<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Block $block
 * @var App\Model\Entity\Media|null $media
 * @var string $type
 * @var string $action
 * @var string $submitLabel
 */
$media = $media ?? null;
$nameErrors = $block->getError('name');
$dataErrors = $block->getError('data');
?>
<div class="cms-block-editor">
    <?php echo $this->Form->create(null, ['url' => $action, 'class' => 'cms-block-form', 'data-block-form' => true]); ?>
        <input type="hidden" name="block_type" value="<?php echo h($type); ?>">

        <label class="cms-field">Name
            <input type="text" name="name" value="<?php echo h($block->name ?? ''); ?>" maxlength="120" required>
            <span class="cms-field__hint">Used to find the block in the library — visitors never see it.</span>
        </label>
        <?php if ($nameErrors !== []) { ?>
            <p class="cms-field__error"><?php echo h(implode(' ', $nameErrors)); ?></p>
        <?php } ?>

        <?php echo $this->element('admin/blocks/fields', ['type' => $type, 'data' => $block->data]); ?>

        <?php if ($dataErrors !== []) { ?>
            <p class="cms-field__error"><?php echo h(implode(' ', $dataErrors)); ?></p>
        <?php } ?>

        <div class="cms-block-form__savebar">
            <button type="submit" class="cms-btn cms-btn--primary"><?php echo h($submitLabel); ?></button>
            <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/blocks">Cancel</a>
            <?php if (!empty($block->id)) { ?>
                <span class="cms-block-form__savebar-spacer"></span>
                <?php echo $this->Form->postLink('Delete', [
                    'action' => 'delete', 'id' => $block->id, '_method' => 'POST',
                ], ['class' => 'cms-btn cms-btn--danger', 'confirm' => 'Delete this block? This cannot be undone.']); ?>
            <?php } ?>
        </div>
    <?php echo $this->Form->end(); ?>

    <aside class="cms-block-preview-pane">
        <div class="cms-block-preview-pane__label">Preview · <?php echo h($block->type->label()); ?></div>
        <div class="cms-block-preview-pane__stage" data-block-preview>
            <?php echo $this->element('admin/blocks/preview', ['block' => $block, 'media' => $media]); ?>
        </div>
        <p class="cms-block-preview-pane__note">How the block looks on a page. Updates as you type.</p>
    </aside>
</div>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/block-media.mjs'); ?>"></script>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/block-preview.mjs'); ?>"></script>
<?php $this->end(); ?>
