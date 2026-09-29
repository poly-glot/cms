<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Collection $collection
 * @var list<App\Model\Enum\CollectionFieldType> $fieldTypes
 * @var array<string, int> $fieldsInUse
 */
$this->assign('title', 'Edit collection — ' . $collection->name);
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Collections', 'url' => $workspacePath . '/admin/collections'],
    ['label' => $collection->name, 'url' => $workspacePath . '/admin/collections/' . (int) $collection->id . '/entries'],
    ['label' => 'Schema', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <?php echo $this->element('admin/collections/collection-title', ['collection' => $collection]); ?>
        <div class="cms-page-header__meta">Define the fields every entry in this collection holds.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/collections/schema-form', [
    'collection' => $collection,
    'fieldTypes' => $fieldTypes,
    'fieldsInUse' => $fieldsInUse,
    'action' => $workspacePath . '/admin/collections/edit/' . (int) $collection->id,
    'submitLabel' => 'Save schema',
]); ?>
