<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Collection $collection
 * @var App\Model\Entity\CollectionEntry $entry
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var array<string, string> $dataErrors
 */
$this->assign('title', 'New entry — ' . $collection->name);
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Collections', 'url' => $workspacePath . '/admin/collections'],
    ['label' => $collection->name, 'url' => $workspacePath . '/admin/collections/' . (int) $collection->id . '/entries'],
    ['label' => 'New', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <?php echo $this->element('admin/collections/entry-title', ['entry' => $entry]); ?>
        <div class="cms-page-header__meta">New <?php echo h($collection->name); ?> entry</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/collections/entry-form', [
    'collection' => $collection,
    'entry' => $entry,
    'statuses' => $statuses,
    'dataErrors' => $dataErrors,
    'action' => $workspacePath . '/admin/collections/' . (int) $collection->id . '/entries/add',
    'submitLabel' => 'Create entry',
]); ?>
