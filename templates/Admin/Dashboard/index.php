<?php
/**
 * @var App\View\AppView $this
 * @var Cake\Collection\CollectionInterface<App\Model\Entity\Block> $blocks
 * @var array<int, App\Model\Entity\Media> $mediaById
 */
$this->assign('title', 'Create');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Create', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Start something new</h1>
        <div class="cms-page-header__meta">Pick a kind of thing to make. Pages hold long-form content; Blocks are reusable pieces you drop into them.</div>
    </div>
</header>

<div class="cms-create">
    <a class="cms-create__tile" href="<?php echo h($workspacePath); ?>/admin/pages/add">
        <span class="cms-create__tile-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4"/></svg></span>
        <div class="cms-create__tile-title">Page</div>
        <div class="cms-create__tile-desc">Long-form content with a slug, template, and revisions.</div>
    </a>
    <a class="cms-create__tile" href="<?php echo h($workspacePath); ?>/admin/posts/add">
        <span class="cms-create__tile-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4z"/><path d="M4 9h16"/><path d="M9 5v14"/></svg></span>
        <div class="cms-create__tile-title">Post</div>
        <div class="cms-create__tile-desc">A dated entry for your journal or blog feed.</div>
    </a>
    <a class="cms-create__tile" href="<?php echo h($workspacePath); ?>/admin/collections/add">
        <span class="cms-create__tile-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3 9h18"/></svg></span>
        <div class="cms-create__tile-title">Collection</div>
        <div class="cms-create__tile-desc">A repeatable schema — products, recipes, case studies.</div>
    </a>
    <a class="cms-create__tile" href="<?php echo h($workspacePath); ?>/admin/media">
        <span class="cms-create__tile-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19l4-12 4 6 4-3 4 9z"/><circle cx="7" cy="6" r="1.5"/></svg></span>
        <div class="cms-create__tile-title">Media</div>
        <div class="cms-create__tile-desc">Upload images, audio, or video into your library.</div>
    </a>
    <a class="cms-create__tile" href="<?php echo h($workspacePath); ?>/admin/navigation">
        <span class="cms-create__tile-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg></span>
        <div class="cms-create__tile-title">Navigation</div>
        <div class="cms-create__tile-desc">Build a menu of links across your pages and sections.</div>
    </a>
    <a class="cms-create__tile" href="<?php echo h($workspacePath); ?>/admin/blocks/add">
        <span class="cms-create__tile-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14v6H5z"/><path d="M5 14h14v6H5z"/></svg></span>
        <div class="cms-create__tile-title">Block</div>
        <div class="cms-create__tile-desc">A reusable component you can drop into any page.</div>
    </a>
</div>

<section class="cms-block-library" aria-labelledby="block-library-heading">
    <div class="cms-block-library__header">
        <div>
            <h2 class="cms-block-library__title" id="block-library-heading">Your blocks</h2>
            <p class="cms-block-library__hint">Reusable pieces — callouts, quotes, statistics, calls to action. Insert them into any page from the editor's toolbar.</p>
        </div>
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/blocks/add">+ New Block</a>
    </div>
    <div class="cms-block-library__grid">
        <?php if ($blocks->isEmpty()) { ?>
            <div class="cms-block-card__empty">No blocks yet. Create one to insert it into any page.</div>
        <?php } ?>
        <?php foreach ($blocks as $block) { ?>
            <?php echo $this->element('admin/block_card', ['block' => $block, 'mediaById' => $mediaById]); ?>
        <?php } ?>
    </div>
</section>
