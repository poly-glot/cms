<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Block $block
 * @var App\Model\Entity\Media|null $previewMedia
 * @var string $type
 */
$this->assign('title', 'Edit block');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Blocks', 'url' => $workspacePath . '/admin/blocks'],
    ['label' => $block->name, 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Edit block</h1>
        <div class="cms-page-header__meta"><?php echo h($block->type->label()); ?> block</div>
    </div>
</header>

<?php echo $this->element('admin/blocks/form', [
    'block' => $block,
    'media' => $previewMedia,
    'type' => $type,
    'action' => $workspacePath . '/admin/blocks/edit/' . (int) $block->id,
    'submitLabel' => 'Save block',
]); ?>
