<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Block $block
 * @var App\Model\Entity\Media|null $previewMedia
 * @var string $type
 * @var array<int, App\Model\Enum\BlockType> $types
 */
$this->assign('title', 'New block');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Blocks', 'url' => $workspacePath . '/admin/blocks'],
    ['label' => 'New', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">New block</h1>
        <?php if ($type === '') { ?>
            <div class="cms-page-header__meta">Pick a kind of block to create. You can insert it into any page from the editor's toolbar.</div>
        <?php } ?>
    </div>
</header>

<?php if ($type === '') { ?>
    <div class="cms-block-types">
        <?php foreach ($types as $blockType) { ?>
            <a class="cms-block-type-tile" href="<?php echo h($workspacePath); ?>/admin/blocks/add?type=<?php echo h($blockType->value); ?>">
                <span class="cms-block-type-tile__icon"><?php echo $this->element('admin/blocks/type_icon', ['type' => $blockType->value]); ?></span>
                <span class="cms-block-type-tile__label"><?php echo h($blockType->label()); ?></span>
                <span class="cms-block-type-tile__desc"><?php echo h($blockType->description()); ?></span>
            </a>
        <?php } ?>
    </div>
<?php } else { ?>
    <?php echo $this->element('admin/blocks/form', [
        'block' => $block,
        'media' => $previewMedia,
        'type' => $type,
        'action' => $workspacePath . '/admin/blocks/add',
        'submitLabel' => 'Create block',
    ]); ?>
<?php } ?>
