<?php
/**
 * @var App\Model\Entity\Page $page
 * @var array<App\Model\Entity\Page> $ancestors
 * @var Cake\View\View $this
 */
$this->assign('title', $page->title);
?>
<?php echo $this->element('breadcrumb', ['ancestors' => $ancestors ?? [], 'page' => $page]); ?>
<article class="page page--landing">
    <header class="page__hero">
        <h1 class="page__title page__title--landing"><?php echo h($page->title); ?></h1>
    </header>
    <?php echo $this->element('page_body', ['page' => $page, 'modifier' => 'prose--landing']); ?>
</article>
