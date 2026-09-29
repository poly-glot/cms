<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Collection $collection
 * @var iterable<App\Model\Entity\CollectionEntry> $entries
 */
$this->assign('title', $collection->name . ' — entries');
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
    ['label' => 'Collections', 'url' => $workspacePath . '/admin/collections'],
    ['label' => $collection->name, 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title"><?php echo h($collection->name); ?></h1>
        <div class="cms-page-header__meta"><?php echo h($collection->description ?? ''); ?></div>
    </div>
    <div class="cms-page-header__actions">
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/collections/edit/<?php echo (int) $collection->id; ?>">Edit schema</a>
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/collections/<?php echo (int) $collection->id; ?>/entries/add">+ New entry</a>
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
    <?php foreach ($entries as $entry) { ?>
        <?php $hasRows = true; ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td"><span class="cms-table__check" aria-hidden="true"></span></div>
            <div class="cms-table__td cms-table__td--title">
                <a href="<?php echo h($workspacePath); ?>/admin/collections/<?php echo (int) $collection->id; ?>/entries/edit/<?php echo (int) $entry->id; ?>"><?php echo h($entry->title); ?></a>
            </div>
            <div class="cms-table__td cms-table__td--muted"><?php echo h($entry->slug); ?></div>
            <div class="cms-table__td"><?php echo h($entry->author?->name ?? '—'); ?></div>
            <div class="cms-table__td cms-table__td--muted">
                <time datetime="<?php echo h($entry->modified?->format(\DATE_ATOM)); ?>"><?php echo h($entry->modified?->timeAgoInWords()); ?></time>
            </div>
            <div class="cms-table__td">
                <span class="cms-pill<?php echo $pillClass($entry->status); ?>">
                    <span class="cms-pill__dot<?php echo $dotClass($entry->status); ?>" aria-hidden="true"></span>
                    <?php echo h($entry->statusEnum->label()); ?>
                </span>
            </div>
        </div>
    <?php } ?>
    <?php if (!$hasRows) { ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td cms-table__td--muted cms-table__empty">No entries yet — create the first one.</div>
        </div>
    <?php } ?>
</div>
