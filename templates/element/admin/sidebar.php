<?php
/**
 * @var Cake\View\View $this
 * @var string $workspacePath
 */
$current = $this->getRequest()->getParam('controller');
$prefix = $this->getRequest()->getParam('prefix');
$base = $workspacePath . '/admin';

$isActive = static fn (string $controller): bool => $prefix === 'Admin' && $current === $controller;

$request = $this->getRequest();
$activeCollectionId = null;
if ($isActive('CollectionEntries')) {
    $activeCollectionId = (int) $request->getParam('collectionId') ?: null;
} elseif ($isActive('Collections') && $request->getParam('action') === 'edit') {
    $activeCollectionId = (int) $request->getParam('id') ?: null;
}

$activeFieldsType = $isActive('ContentFields') ? (string) $request->getParam('type') : '';

$navItem = static function (string $label, string $href, bool $active, string $badge = ''): string {
    $classes = 'cms-sidebar__item' . ($active ? ' cms-sidebar__item--active' : '');
    $currentAttr = $active ? ' aria-current="page"' : '';

    return sprintf(
        '<li><a class="%s" href="%s"%s>%s%s</a></li>',
        h($classes),
        h($href),
        $currentAttr,
        h($label),
        $badge,
    );
};

$pagesActive = $isActive('Pages') || $activeFieldsType === 'pages';
$postsActive = $isActive('Posts') || $activeFieldsType === 'posts';

$collectionsActive = $isActive('Collections') || $isActive('CollectionEntries');
$collectionsClasses = 'cms-sidebar__item' . ($collectionsActive ? ' cms-sidebar__item--active' : '');
?>
<nav class="cms-sidebar" aria-label="Primary">
    <section class="cms-sidebar__section">
        <h2 class="cms-sidebar__heading">Content</h2>
        <ul>
            <?php echo $navItem('Create', $base, $isActive('Dashboard')); ?>
            <?php echo $navItem('Pages', $base . '/pages', $pagesActive); ?>
            <?php echo $navItem('Posts', $base . '/posts', $postsActive); ?>
            <li>
                <a class="<?php echo h($collectionsClasses); ?>" href="<?php echo h($base . '/collections'); ?>"<?php echo $collectionsActive ? ' aria-current="page"' : ''; ?>>Collections</a>
                <?php echo $this->cell('CollectionsNav', [$activeCollectionId]); ?>
            </li>
            <?php echo $navItem('Blocks', $base . '/blocks', $isActive('Blocks')); ?>
            <?php echo $navItem('Comments', $base . '/comments', $isActive('Comments'), (string) $this->cell('CommentsBadge')); ?>
            <?php echo $navItem('Media', $base . '/media', $isActive('Media')); ?>
        </ul>
    </section>
    <section class="cms-sidebar__section">
        <h2 class="cms-sidebar__heading">Site</h2>
        <ul>
            <?php echo $navItem('Appearance', $base . '/appearance', $isActive('Appearance')); ?>
            <?php echo $navItem('Navigation', $base . '/navigation', $isActive('Navigation')); ?>
            <?php echo $navItem('Settings', $base . '/settings', $isActive('Settings')); ?>
            <?php echo $navItem('Users', $base . '/users', $isActive('Users')); ?>
            <?php echo $navItem('API tokens', $base . '/tokens', $isActive('Tokens')); ?>
            <?php echo $navItem('API playground', $base . '/api-playground', $isActive('ApiPlayground')); ?>
        </ul>
    </section>
</nav>
