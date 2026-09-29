<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\Post> $posts
 * @var array<int, string> $authors
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var list<array{slug: string, label: string}> $selectedTags
 * @var string $tagMode
 * @var string $keyword
 * @var array{q:?string, status:?string, author_id:?string, tags:array<int, string>} $activeFilters
 */
$this->assign('title', 'All posts');
$appliedFilters = array_filter($activeFilters ?? []);
$activeCount = count($appliedFilters);
$filtersOpen = $activeCount > 0;
$pillClass = static fn (string $status): string => match ($status) {
    'draft' => ' cms-pill--draft',
    'scheduled' => ' cms-pill--scheduled',
    default => '',
};
$dotClass = static fn (string $status): string => match ($status) {
    'draft' => ' cms-pill__dot--draft',
    'scheduled' => ' cms-pill__dot--scheduled',
    default => '',
};
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Posts', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">All posts</h1>
        <div class="cms-page-header__meta"><?php echo $this->Paginator->counter('{{count}} posts'); ?></div>
    </div>
    <div class="cms-page-header__actions">
        <details class="cms-filter" <?php echo $filtersOpen ? 'open' : ''; ?>>
            <summary class="cms-btn<?php echo $activeCount > 0 ? ' cms-btn--active' : ''; ?>">
                Filter<?php if ($activeCount > 0) { ?><span class="cms-filter__count"><?php echo $activeCount; ?></span><?php } ?>
            </summary>
            <form method="get" action="<?php echo h($workspacePath); ?>/admin/posts" class="cms-filter__panel">
                <div class="cms-filter__grid">
                    <label class="cms-filter__field cms-filter__field--full">Search
                        <input type="search" name="q" value="<?php echo h($keyword); ?>" placeholder="Search by title or slug&hellip;">
                    </label>
                    <label class="cms-filter__field">Status
                        <select name="status">
                            <option value="">All</option>
                            <?php foreach ($statuses as $status) { ?>
                                <option value="<?php echo h($status->value); ?>" <?php echo ($activeFilters['status'] ?? '') === $status->value ? 'selected' : ''; ?>>
                                    <?php echo h($status->label()); ?>
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
                        <a href="<?php echo h($workspacePath); ?>/admin/posts" class="cms-btn">Clear all</a>
                    <?php } ?>
                    <button type="submit" class="cms-btn cms-btn--primary">Apply filters</button>
                </div>
            </form>
        </details>
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/content-model/posts/fields">Edit schema</a>
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/posts/add">+ New post</a>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-table" role="table">
    <div class="cms-table__head" role="row">
        <div class="cms-table__th" role="columnheader"></div>
        <div class="cms-table__th" role="columnheader">Title</div>
        <div class="cms-table__th" role="columnheader">Slug</div>
        <div class="cms-table__th" role="columnheader">Author</div>
        <div class="cms-table__th" role="columnheader">Modified</div>
        <div class="cms-table__th" role="columnheader">Status</div>
    </div>
    <?php $hasRows = false; ?>
    <?php foreach ($posts as $post) { ?>
        <?php $hasRows = true; ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td"><span class="cms-table__check" aria-hidden="true"></span></div>
            <div class="cms-table__td cms-table__td--title">
                <a href="<?php echo h($workspacePath); ?>/admin/posts/edit/<?php echo (int) $post->id; ?>"><?php echo h($post->title); ?></a>
            </div>
            <div class="cms-table__td cms-table__td--muted">/posts/<?php echo h($post->slug); ?></div>
            <div class="cms-table__td"><?php echo h($post->author?->name ?? '—'); ?></div>
            <div class="cms-table__td cms-table__td--muted">
                <time datetime="<?php echo h($post->modified?->format(\DATE_ATOM)); ?>"><?php echo h($post->modified?->timeAgoInWords()); ?></time>
            </div>
            <div class="cms-table__td">
                <span class="cms-pill<?php echo $pillClass($post->status); ?>">
                    <span class="cms-pill__dot<?php echo $dotClass($post->status); ?>" aria-hidden="true"></span>
                    <?php echo h($post->statusEnum->label()); ?>
                </span>
            </div>
        </div>
    <?php } ?>
    <?php if (!$hasRows) { ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td cms-table__td--muted cms-table__empty">No posts match these filters.</div>
        </div>
    <?php } ?>
</div>

<?php $this->Paginator->options(['url' => ['?' => array_diff_key($this->request->getQueryParams(), ['page' => null])]]); ?>
<?php if ($this->Paginator->total() > 1) { ?>
    <nav class="cms-pagination" aria-label="Pages">
        <?php echo $this->Paginator->prev('‹ Prev'); ?>
        <?php echo $this->Paginator->numbers(); ?>
        <?php echo $this->Paginator->next('Next ›'); ?>
    </nav>
<?php } ?>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/tag-filter.mjs'); ?>"></script>
<?php $this->end(); ?>
