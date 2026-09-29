<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Post $post
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var array<int, App\Model\Entity\User> $authors
 * @var App\Model\Entity\ContentTypeFieldSchema $fieldSchema
 * @var array<string, string> $dataErrors
 */
$this->assign('title', 'New post');
?>
<?php echo $this->element('admin/breadcrumb', [
    'crumbs' => [
        ['label' => 'Home', 'url' => $workspacePath . '/admin'],
        ['label' => 'Posts', 'url' => $workspacePath . '/admin/posts'],
        ['label' => 'New post', 'url' => null],
    ],
]); ?>

<header class="cms-page-header">
    <div>
        <div class="cms-page-header__title-row">
            <h1 class="cms-page-header__title" data-title-input contenteditable="true" role="textbox" aria-label="Post title" data-placeholder="Untitled post"></h1>
            <button type="button" class="cms-title-edit" data-title-edit>Edit</button>
        </div>
        <div class="cms-page-header__meta">A dated entry for your journal or blog feed.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/posts/form', ['post' => $post, 'statuses' => $statuses, 'authors' => $authors ?? [], 'fieldSchema' => $fieldSchema, 'dataErrors' => $dataErrors ?? [], 'submitLabel' => 'Create post']); ?>
