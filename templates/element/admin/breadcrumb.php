<?php
/**
 * @var Cake\View\View $this
 * @var array<array{label: string, url: string|null}> $crumbs
 */
?>
<nav class="cms-breadcrumb" aria-label="Breadcrumb">
    <?php foreach ($crumbs as $i => $crumb) { ?>
        <?php if ($i > 0) { ?>
            <span class="cms-breadcrumb__sep" aria-hidden="true">›</span>
        <?php } ?>
        <?php if ($crumb['url'] !== null) { ?>
            <a class="cms-breadcrumb__link" href="<?php echo h($crumb['url']); ?>"><?php echo h($crumb['label']); ?></a>
        <?php } else { ?>
            <span class="cms-breadcrumb__current"><?php echo h($crumb['label']); ?></span>
        <?php } ?>
    <?php } ?>
</nav>
