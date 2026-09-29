<?php
/**
 * @var Cake\View\View $this
 * @var array<App\Model\Entity\Page> $ancestors
 * @var App\Model\Entity\Page $page
 */
?>
<?php if (!empty($ancestors)) { ?>
<nav class="page-breadcrumb" aria-label="Breadcrumb">
    <ol>
        <li><a href="/">Home</a></li>
        <?php
        $crumbPath = '';
    foreach ($ancestors as $ancestor) {
        $crumbPath = ltrim($crumbPath . '/' . $ancestor->slug, '/');
        ?>
            <li><a href="<?php echo h('/' . $crumbPath); ?>"><?php echo h($ancestor->title); ?></a></li>
        <?php } ?>
        <li aria-current="page"><?php echo h($page->title); ?></li>
    </ol>
</nav>
<?php } ?>
