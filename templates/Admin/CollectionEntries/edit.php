<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Collection $collection
 * @var App\Model\Entity\CollectionEntry $entry
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var array<string, string> $dataErrors
 */
$this->assign('title', $entry->title);
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Collections', 'url' => $workspacePath . '/admin/collections'],
    ['label' => $collection->name, 'url' => $workspacePath . '/admin/collections/' . (int) $collection->id . '/entries'],
    ['label' => $entry->title, 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <?php echo $this->element('admin/collections/entry-title', ['entry' => $entry]); ?>
        <div class="cms-page-header__meta">
            Last edited by <strong><?php echo h($entry->author?->name ?? 'Admin'); ?></strong>
            <?php if ($entry->modified !== null) { ?>
                · <time datetime="<?php echo h($entry->modified->format(\DATE_ATOM)); ?>"><?php echo h($entry->modified->timeAgoInWords()); ?></time>
            <?php } ?>
        </div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/collections/entry-form', [
    'collection' => $collection,
    'entry' => $entry,
    'statuses' => $statuses,
    'dataErrors' => $dataErrors,
    'action' => $workspacePath . '/admin/collections/' . (int) $collection->id . '/entries/edit/' . (int) $entry->id,
    'submitLabel' => 'Save entry',
]); ?>
