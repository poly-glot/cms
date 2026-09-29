<?php
/**
 * @var App\View\AppView $this
 * @var Cake\Collection\CollectionInterface<App\Model\Entity\Block> $blocks
 * @var array<int, App\Model\Entity\Media> $mediaById
 * @var string|null $activeType
 * @var array<int, App\Model\Enum\BlockType> $types
 */
$this->assign('title', 'Blocks');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Blocks', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Your blocks</h1>
        <div class="cms-page-header__meta">Reusable pieces — callouts, quotes, statistics, calls to action. Insert them into any page from the editor's toolbar.</div>
    </div>
    <div class="cms-page-header__actions">
        <details class="cms-filter">
            <summary class="cms-btn">Filter</summary>
            <form method="get" action="<?php echo h($workspacePath); ?>/admin/blocks" class="cms-filter__panel">
                <label>Type
                    <select name="type" data-autosubmit>
                        <option value="">All types</option>
                        <?php foreach ($types as $type) { ?>
                            <option value="<?php echo h($type->value); ?>" <?php echo $activeType === $type->value ? 'selected' : ''; ?>>
                                <?php echo h($type->label()); ?>
                            </option>
                        <?php } ?>
                    </select>
                </label>
            </form>
        </details>
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/blocks/add">+ New Block</a>
    </div>
</header>

<section class="cms-block-library">
    <div class="cms-block-library__grid">
        <?php if ($blocks->isEmpty()) { ?>
            <div class="cms-block-card__empty">No blocks yet. Create one to insert it into any page.</div>
        <?php } ?>
        <?php foreach ($blocks as $block) { ?>
            <?php echo $this->element('admin/block_card', ['block' => $block, 'mediaById' => $mediaById]); ?>
        <?php } ?>
    </div>
</section>
