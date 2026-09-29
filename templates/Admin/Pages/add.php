<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Page $page
 * @var App\Model\Entity\ContentTypeFieldSchema $fieldSchema
 * @var array<string, string> $dataErrors
 */
$this->assign('title', 'New page');
?>
<?php echo $this->element('admin/breadcrumb', [
    'crumbs' => [
        ['label' => 'Home', 'url' => $workspacePath . '/admin'],
        ['label' => 'Pages', 'url' => $workspacePath . '/admin/pages'],
        ['label' => 'New page', 'url' => null],
    ],
]); ?>

<header class="cms-page-header">
    <div>
        <?php echo $this->element('admin/pages/title_field', ['page' => $page]); ?>
        <div class="cms-page-header__meta">Give your page a title, then start writing below.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/pages/form', ['page' => $page, 'fieldSchema' => $fieldSchema, 'dataErrors' => $dataErrors ?? [], 'submitLabel' => 'Create page']); ?>
