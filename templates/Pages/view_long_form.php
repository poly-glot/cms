<?php
/**
 * @var App\Model\Entity\Page $page
 * @var array<App\Model\Entity\Page> $ancestors
 * @var Cake\View\View $this
 */
$this->assign('title', $page->title);
?>
<?php echo $this->element('breadcrumb', ['ancestors' => $ancestors ?? [], 'page' => $page]); ?>
<article class="page page--long-form">
    <header class="page__header">
        <h1 class="page__title page__title--long"><?php echo h($page->title); ?></h1>
        <?php if ($page->author !== null) { ?>
            <div class="page__meta">
                <span class="page__byline">by <?php echo h($page->author->name); ?></span>
                <time datetime="<?php echo h($page->modified->format('Y-m-d')); ?>" class="page__date">
                    <?php echo h($page->modified->format('j M Y')); ?>
                </time>
            </div>
        <?php } ?>
    </header>
    <?php echo $this->element('page_body', ['page' => $page, 'modifier' => 'prose--long-form']); ?>
</article>
<?php echo $this->element('comments', ['page' => $page, 'comments' => $comments ?? [], 'allowComments' => $allowComments ?? true]); ?>
