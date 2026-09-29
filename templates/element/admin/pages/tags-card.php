<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page|App\Model\Entity\Post $entity
 * @var string $segment
 */
$tags = $entity->tags ?? [];
?>
<section class="cms-card cms-side-card cms-side-card--pop" data-tags-card data-tags-base="<?php echo h($workspacePath); ?>/admin/<?php echo h($segment); ?>/<?php echo (int) $entity->id; ?>/tags">
    <header class="cms-side-card__header"><h2>Tags</h2></header>
    <div class="cms-tags">
        <ul class="cms-tag-list" data-tag-list>
            <?php foreach ($tags as $tag) { ?>
                <li class="cms-tag" data-tag-slug="<?php echo h($tag->slug); ?>">
                    <span class="cms-tag__label"><?php echo h($tag->label); ?></span>
                    <button type="button" class="cms-tag__remove" data-tag-remove aria-label="Remove <?php echo h($tag->label); ?>">&times;</button>
                </li>
            <?php } ?>
        </ul>
        <button type="button" class="cms-tag cms-tag--add" data-add-tag>+ add</button>
        <div class="cms-tag-input" data-tag-input-wrap hidden>
            <input type="text" placeholder="Add a tag" autocomplete="off" data-tag-input>
            <div class="cms-tag-suggest" data-tag-suggest hidden></div>
        </div>
    </div>
</section>
