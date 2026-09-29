<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page $page
 * @var array<int, string> $treeList
 */
$templateLabels = [
    'default' => 'Default',
    'long_form' => 'Long-form',
    'landing' => 'Landing',
];
$templateKey = (string) ($page->template ?? 'default');
$templateLabel = $templateLabels[$templateKey] ?? $templateLabels['default'];
$parentLabel = $page->parent_page?->title ?? '(root)';
$position = (int) ($page->position ?? 0);
?>
<section class="cms-card cms-side-card" data-details-card>
    <header class="cms-side-card__header">
        <h2>Details</h2>
        <button class="cms-btn cms-btn--ghost cms-card__edit" type="button" data-details-edit aria-expanded="false">Edit</button>
    </header>

    <div class="cms-card__read" data-details-read>
        <div class="cms-card__row">
            <span class="cms-card__label">Template</span>
            <span class="cms-card__value"><?php echo h($templateLabel); ?></span>
        </div>
        <div class="cms-card__row">
            <span class="cms-card__label">Parent</span>
            <span class="cms-card__value"><?php echo h($parentLabel); ?></span>
        </div>
        <div class="cms-card__row">
            <span class="cms-card__label">Order</span>
            <span class="cms-card__value">position <?php echo h($position); ?></span>
        </div>
        <div class="cms-card__row">
            <span class="cms-card__label">Visibility</span>
            <span class="cms-card__value">Public</span>
        </div>
    </div>

    <div class="cms-card__edit-fields" data-details-fields hidden>
        <div class="cms-card__field">
            <label class="cms-card__field-label" for="details-template">Template</label>
            <?php echo $this->Form->select('template', $templateLabels, [
                'id' => 'details-template',
                'default' => $templateKey,
                'class' => 'cms-card__control',
            ]); ?>
        </div>
        <div class="cms-card__field">
            <label class="cms-card__field-label" for="details-parent">Parent</label>
            <?php echo $this->Form->select('parent_id', $treeList ?? [], [
                'id' => 'details-parent',
                'empty' => '(root)',
                'default' => $page->parent_id,
                'class' => 'cms-card__control',
            ]); ?>
        </div>
        <div class="cms-card__field">
            <label class="cms-card__field-label" for="details-position">Order</label>
            <?php echo $this->Form->control('position', [
                'id' => 'details-position',
                'type' => 'number',
                'min' => 0,
                'default' => $position,
                'label' => false,
                'class' => 'cms-card__control',
            ]); ?>
        </div>
        <div class="cms-card__field">
            <label class="cms-card__field-toggle">
                <?php echo $this->Form->checkbox('comments_enabled', ['checked' => $page->comments_enabled ?? true]); ?>
                Allow comments on this page
            </label>
        </div>
    </div>
</section>
