<?php
/**
 * @var App\View\AppView $this
 * @var list<array{slug: string, name: string}> $tabs
 * @var array<string, mixed> $navData
 */
$this->assign('title', 'Navigation');
$jsonFlags = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_SLASHES;
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Navigation', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Navigation</h1>
        <div class="cms-page-header__meta">Drag items to reorder. Drop an item onto another to nest it underneath.</div>
    </div>
    <div class="cms-page-header__actions">
        <button class="cms-btn" type="button" data-nav-add-page>+ Page</button>
        <button class="cms-btn" type="button" data-nav-add-url>+ Link</button>
        <button class="cms-btn cms-btn--primary" type="button" data-nav-save>Save menu</button>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-nav-builder">
    <div>
        <div class="cms-nav-menus">
            <span class="cms-nav-menus__label">Menu</span>
            <div class="cms-nav-menus__tabs" data-nav-menus role="tablist">
                <?php foreach ($tabs as $index => $tab) { ?>
                    <button
                        class="cms-nav-menus__tab<?php echo $index === 0 ? ' cms-nav-menus__tab--active' : ''; ?>"
                        type="button"
                        data-menu-id="<?php echo h($tab['slug']); ?>"
                    ><?php echo h($tab['name']); ?></button>
                <?php } ?>
            </div>
            <span class="cms-nav-menus__count" data-nav-count></span>
        </div>

        <div class="cms-nav-tree-card">
            <div class="cms-nav-tree-card__toolbar">
                <button class="cms-btn" type="button" data-nav-expand-all style="height:24px; padding:0 10px; font-size:11px;">Expand all</button>
                <button class="cms-btn" type="button" data-nav-collapse-all style="height:24px; padding:0 10px; font-size:11px;">Collapse all</button>
                <span class="cms-nav-tree-card__hint">Drop into an item to nest &middot; Drop above/below to reorder</span>
            </div>
            <div class="cms-nav-tree" data-nav-tree role="tree"></div>
            <div class="cms-nav-quickadd">
                <button class="cms-btn" type="button" data-nav-add-page>+ Add page link</button>
                <button class="cms-btn" type="button" data-nav-add-url>+ Add external URL</button>
            </div>
        </div>
    </div>

    <aside class="cms-nav-details" data-nav-details aria-label="Item details"></aside>
</div>

<script type="application/json" data-nav-data><?php echo json_encode($navData, $jsonFlags); ?></script>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/navigation.mjs'); ?>"></script>
<?php $this->end(); ?>
