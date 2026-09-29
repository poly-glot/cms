<?php
/**
 * Admin block-library card: a content-block preview window over a metal meta
 * footer (name + type pill). Shared by the dashboard "Your blocks" section and
 * the /admin/blocks index. The preview window itself is the shared
 * admin/blocks/preview element, so the card and the editor render identically.
 *
 * @var App\View\AppView $this
 * @var App\Model\Entity\Block $block
 * @var array<int, App\Model\Entity\Media> $mediaById
 */
$mediaById = $mediaById ?? [];
$media = $block->type->referencesMedia()
    ? ($mediaById[(int) ($block->data['media_id'] ?? 0)] ?? null)
    : null;
?>
<a class="cms-block-card" href="<?php echo h($workspacePath); ?>/admin/blocks/edit/<?php echo (int) $block->id; ?>">
    <div class="cms-block-card__preview">
        <?php echo $this->element('admin/blocks/preview', ['block' => $block, 'media' => $media]); ?>
    </div>
    <div class="cms-block-card__meta">
        <span><?php echo h($block->name); ?></span>
        <span class="cms-block-card__type"><?php echo h($block->type->chipLabel()); ?></span>
    </div>
</a>
