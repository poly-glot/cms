<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\Collection> $collections
 */
$this->assign('title', 'Collections');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Collections', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Collections</h1>
        <div class="cms-page-header__meta">Custom content types — define a structure once, then add entries.</div>
    </div>
    <div class="cms-page-header__actions">
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/collections/add">+ New collection</a>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-table cms-table--types" role="table">
    <div class="cms-table__head" role="row">
        <div class="cms-table__th" role="columnheader"></div>
        <div class="cms-table__th" role="columnheader">Name</div>
        <div class="cms-table__th" role="columnheader">Slug</div>
        <div class="cms-table__th" role="columnheader">Fields</div>
        <div class="cms-table__th" role="columnheader">Updated</div>
    </div>
    <?php $hasRows = false; ?>
    <?php foreach ($collections as $collection) {
        $hasRows = true;
        $fieldCount = count($collection->fields);
        ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td"><span class="cms-table__check" aria-hidden="true"></span></div>
            <div class="cms-table__td cms-table__td--title">
                <a href="<?php echo h($workspacePath); ?>/admin/collections/<?php echo (int) $collection->id; ?>/entries"><?php echo h($collection->name); ?></a>
            </div>
            <div class="cms-table__td cms-table__td--muted"><?php echo h($collection->slug); ?></div>
            <div class="cms-table__td">
                <a class="cms-table__link" href="<?php echo h($workspacePath); ?>/admin/collections/edit/<?php echo (int) $collection->id; ?>"><?php echo $fieldCount; ?> field<?php echo $fieldCount === 1 ? '' : 's'; ?></a>
            </div>
            <div class="cms-table__td cms-table__td--muted">
                <time datetime="<?php echo h($collection->modified?->format(\DATE_ATOM)); ?>"><?php echo h($collection->modified?->timeAgoInWords()); ?></time>
            </div>
        </div>
    <?php } ?>
    <?php if (!$hasRows) { ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td cms-table__td--muted cms-table__empty">No collections yet — create your first content type.</div>
        </div>
    <?php } ?>
</div>
