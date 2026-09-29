<?php
/**
 * @var Cake\View\View $this
 * @var list<App\Model\Entity\Collection> $collections
 * @var int|null $activeId
 */
if ($collections === []) {
    return;
}
?>
<ul class="cms-sidebar__sublist">
    <?php foreach ($collections as $collection) {
        $isActive = $activeId === (int) $collection->id;
        $classes = 'cms-sidebar__subitem' . ($isActive ? ' cms-sidebar__subitem--active' : '');
        $current = $isActive ? ' aria-current="page"' : '';
        $href = $this->Url->build([
            'prefix' => 'Admin',
            'controller' => 'CollectionEntries',
            'action' => 'index',
            'collectionId' => (int) $collection->id,
        ]);
        ?>
        <li>
            <a class="<?php echo h($classes); ?>" href="<?php echo h($href); ?>"<?php echo $current; ?>>
                <?php echo h($collection->name); ?>
            </a>
        </li>
    <?php } ?>
</ul>
