<?php
/**
 * @var App\View\AppView $this
 * @var array<int, App\Model\Entity\Page> $tree
 * @var bool $filtered
 * @var array<int, string> $authors
 * @var array<int, string> $parentOptions
 * @var list<array{slug: string, label: string}> $selectedTags
 * @var string $tagMode
 * @var array{status:?string, author_id:?string, tags:array<int, string>, parent_id:?string} $activeFilters
 */
$this->assign('title', 'All pages');
$appliedFilters = array_filter($activeFilters ?? []);
$activeCount = count($appliedFilters);
$filtersOpen = $activeCount > 0;
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Pages', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <h1 class="cms-page-header__title">All pages</h1>
    <div class="cms-page-header__actions">
        <details class="cms-filter" <?php echo $filtersOpen ? 'open' : ''; ?>>
            <summary class="cms-btn<?php echo $activeCount > 0 ? ' cms-btn--active' : ''; ?>">
                Filter<?php if ($activeCount > 0) { ?><span class="cms-filter__count"><?php echo $activeCount; ?></span><?php } ?>
            </summary>
            <form method="get" action="<?php echo h($workspacePath); ?>/admin/pages" class="cms-filter__panel">
                <div class="cms-filter__grid">
                    <label class="cms-filter__field">Status
                        <select name="status">
                            <option value="">All</option>
                            <option value="draft" <?php echo ($activeFilters['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="live" <?php echo ($activeFilters['status'] ?? '') === 'live' ? 'selected' : ''; ?>>Live</option>
                            <option value="scheduled" <?php echo ($activeFilters['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        </select>
                    </label>
                    <label class="cms-filter__field">Parent
                        <select name="parent_id">
                            <option value="">Any</option>
                            <?php foreach ($parentOptions as $parentId => $parentLabel) { ?>
                                <option value="<?php echo (int) $parentId; ?>" <?php echo (string) ($activeFilters['parent_id'] ?? '') === (string) $parentId ? 'selected' : ''; ?>>
                                    <?php echo h($parentLabel); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>
                    <label class="cms-filter__field">Author
                        <select name="author_id">
                            <option value="">Any</option>
                            <?php foreach ($authors as $authorId => $authorName) { ?>
                                <option value="<?php echo (int) $authorId; ?>" <?php echo (string) ($activeFilters['author_id'] ?? '') === (string) $authorId ? 'selected' : ''; ?>>
                                    <?php echo h($authorName); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>
                    <?php echo $this->element('admin/tag_filter', [
                        'selectedTags' => $selectedTags,
                        'tagMode' => $tagMode,
                    ]); ?>
                </div>
                <div class="cms-filter__actions">
                    <?php if ($activeCount > 0) { ?>
                        <a href="<?php echo h($workspacePath); ?>/admin/pages" class="cms-btn">Clear all</a>
                    <?php } ?>
                    <button type="submit" class="cms-btn cms-btn--primary">Apply filters</button>
                </div>
            </form>
        </details>
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/content-model/pages/fields">Edit schema</a>
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/pages/add">+ New page</a>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->Form->create(null, ['url' => ['action' => 'bulkStatus', '_method' => 'POST'], 'id' => 'pages-bulk-form']); ?>
<div class="cms-bulkbar" data-bulkbar hidden>
    <span class="cms-bulkbar__count"><span data-bulk-count>0</span> selected</span>
    <span class="cms-bulkbar__spacer"></span>
    <select name="status" class="cms-bulkbar__select" aria-label="Set status">
        <option value="">Set status…</option>
        <option value="draft">Draft</option>
        <option value="live">Published</option>
    </select>
    <button type="submit" class="cms-btn cms-btn--primary">Apply</button>
    <button type="button" class="cms-btn" data-bulk-clear>Clear</button>
</div>

<div class="cms-tree__head">
    <span class="cms-tree__head-select"><input type="checkbox" data-select-all aria-label="Select all pages"></span>
    <span class="cms-tree__head-title">Title<?php echo $filtered ? ' · filtered (reordering off)' : ''; ?></span>
    <span class="cms-tree__head-col">Slug</span>
    <span class="cms-tree__head-col">Author</span>
    <span class="cms-tree__head-col">Status</span>
</div>

<?php if ($tree === []) { ?>
    <p class="cms-tree__empty">No pages yet. Create your first page to get started.</p>
<?php } else { ?>
    <ul class="cms-tree" data-pages-tree data-reorder-url="<?php echo h($workspacePath); ?>/admin/pages/reorder">
        <?php foreach ($tree as $page) { ?>
            <?php echo $this->element('admin/pages/tree_row', ['page' => $page, 'depth' => 0, 'draggable' => !$filtered]); ?>
        <?php } ?>
    </ul>
<?php } ?>
<?php echo $this->Form->end(); ?>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/pages-index.mjs'); ?>"></script>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/tag-filter.mjs'); ?>"></script>
<?php $this->end(); ?>
