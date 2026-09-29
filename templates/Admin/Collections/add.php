<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Collection $collection
 * @var list<App\Model\Enum\CollectionFieldType> $fieldTypes
 * @var array<string, int> $fieldsInUse
 */
$this->assign('title', 'New collection');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Collections', 'url' => $workspacePath . '/admin/collections'],
    ['label' => 'New', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <?php echo $this->element('admin/collections/collection-title', ['collection' => $collection]); ?>
        <div class="cms-page-header__meta">Define a repeatable schema — products, recipes, case studies.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/collections/schema-form', [
    'collection' => $collection,
    'fieldTypes' => $fieldTypes,
    'fieldsInUse' => $fieldsInUse,
    'action' => $workspacePath . '/admin/collections/add',
    'submitLabel' => 'Create collection',
]); ?>
