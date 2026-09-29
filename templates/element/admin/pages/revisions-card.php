<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page $page
 * @var iterable<App\Model\Entity\PageRevision> $revisions
 */
?>
<section class="cms-card cms-side-card" data-revisions-card data-page-id="<?php echo (int) $page->id; ?>">
    <header class="cms-side-card__header"><h2>Revisions</h2></header>
    <?php if (empty($revisions)) { ?>
        <p class="cms-side-card__empty">No revisions yet &mdash; save the page to create one.</p>
    <?php } else { ?>
        <ol class="cms-revisions">
            <?php foreach ($revisions as $revision) { ?>
                <li class="cms-revisions__row">
                    <span class="cms-revisions__label">v<?php echo (int) $revision->version; ?> &mdash; <?php echo h($revision->created->timeAgoInWords()); ?></span>
                    <button type="button" class="cms-revisions__restore" data-restore-version="<?php echo (int) $revision->version; ?>">Restore</button>
                </li>
            <?php } ?>
        </ol>
    <?php } ?>
</section>
