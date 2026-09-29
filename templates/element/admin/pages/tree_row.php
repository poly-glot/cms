<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page $page
 * @var bool $draggable
 * @var int $depth
 */
$pillClass = match ($page->status) {
    'draft' => ' cms-pill--draft',
    'scheduled' => ' cms-pill--scheduled',
    default => '',
};
$dotClass = match ($page->status) {
    'draft' => ' cms-pill__dot--draft',
    'scheduled' => ' cms-pill__dot--scheduled',
    default => '',
};
?>
<li class="cms-tree__node" data-page-id="<?php echo (int) $page->id; ?>" data-parent-id="<?php echo $page->parent_id !== null ? (int) $page->parent_id : ''; ?>">
    <div class="cms-tree__row" data-tree-row<?php echo $draggable ? ' draggable="true"' : ''; ?>>
        <?php if ($draggable) { ?>
            <span class="cms-tree__handle" aria-hidden="true" title="Drag to reorder">⠿</span>
        <?php } ?>
        <input type="checkbox" class="cms-tree__check" data-tree-check name="ids[]" value="<?php echo (int) $page->id; ?>" aria-label="Select <?php echo h($page->title); ?>">
        <a class="cms-tree__title" href="<?php echo h($workspacePath); ?>/admin/pages/edit/<?php echo (int) $page->id; ?>"><?php echo h($page->title); ?></a>
        <span class="cms-tree__slug">/<?php echo h($page->slug); ?></span>
        <span class="cms-tree__author"><?php echo h($page->author?->name ?? '—'); ?></span>
        <span class="cms-tree__status">
            <span class="cms-pill<?php echo $pillClass; ?>">
                <span class="cms-pill__dot<?php echo $dotClass; ?>" aria-hidden="true"></span>
                <?php echo h(ucfirst($page->status)); ?>
            </span>
        </span>
    </div>
    <ul class="cms-tree__children" data-tree-children>
        <?php foreach ($page->children ?? [] as $child) { ?>
            <?php echo $this->element('admin/pages/tree_row', ['page' => $child, 'depth' => $depth + 1, 'draggable' => $draggable]); ?>
        <?php } ?>
    </ul>
</li>
