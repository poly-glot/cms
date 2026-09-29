<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Post $post
 * @var list<App\Model\Enum\PostStatus> $statuses
 * @var array<int, App\Model\Entity\User> $authors
 * @var App\Model\Entity\ContentTypeFieldSchema $fieldSchema
 * @var array<string, string> $dataErrors
 */
$this->assign('title', 'Edit post — ' . $post->title);
?>
<?php echo $this->element('admin/breadcrumb', [
    'crumbs' => [
        ['label' => 'Home', 'url' => $workspacePath . '/admin'],
        ['label' => 'Posts', 'url' => $workspacePath . '/admin/posts'],
        ['label' => $post->title, 'url' => null],
    ],
]); ?>

<header class="cms-page-header">
    <div>
        <div class="cms-page-header__title-row">
            <h1 class="cms-page-header__title" data-title-input contenteditable="true" role="textbox" aria-label="Post title" data-placeholder="Untitled post"><?php echo h($post->title); ?></h1>
            <button type="button" class="cms-title-edit" data-title-edit>Edit</button>
        </div>
        <div class="cms-page-header__meta">
            <?php echo h($post->statusEnum->label()); ?>
            <?php if ($post->published_at !== null) { ?>
                · published <?php echo h($post->published_at->nice()); ?>
            <?php } ?>
        </div>
    </div>
    <div class="cms-page-header__actions">
        <a
            class="cms-btn cms-btn--ghost"
            href="<?php echo h($workspacePath); ?>/blog/<?php echo h($post->slug); ?>"
            target="_blank"
            rel="noopener"
        >Preview</a>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/posts/form', ['post' => $post, 'statuses' => $statuses, 'authors' => $authors ?? [], 'fieldSchema' => $fieldSchema, 'dataErrors' => $dataErrors ?? [], 'submitLabel' => 'Save post']); ?>
