<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page|App\Model\Entity\Post $entity
 * @var array<int, App\Model\Entity\User> $authors
 * @var bool $pop
 */
$selected = $entity->author;
if ($selected === null && $entity->author_id !== null) {
    foreach ($authors as $candidate) {
        if ($candidate->id === $entity->author_id) {
            $selected = $candidate;
            break;
        }
    }
}
$selectedId = $selected->id ?? $entity->author_id;
?>
<section class="cms-card cms-side-card<?php echo $pop ? ' cms-side-card--pop' : ''; ?>">
    <header class="cms-side-card__header"><h2>Author</h2></header>
    <div class="cms-author-body" data-author-card>
        <?php echo $this->Form->hidden('author_id', ['value' => $selectedId, 'data-author-id' => '1']); ?>
        <button class="cms-author cms-author--pickable" type="button" popovertarget="author-picker" aria-haspopup="listbox">
            <span class="cms-author__avatar" aria-hidden="true" data-author-avatar style="<?php echo h($selected->avatarStyle ?? ''); ?>"></span>
            <span class="cms-author__info">
                <span class="cms-author__name" data-author-name><?php echo h($selected->name ?? 'Unassigned'); ?></span>
                <span class="cms-author__sub" data-author-sub><?php echo h($selected->email ?? 'No author selected'); ?></span>
            </span>
            <span class="cms-author__caret" aria-hidden="true"></span>
        </button>
        <div class="cms-author-picker" id="author-picker" popover data-author-picker role="listbox">
            <?php foreach ($authors as $author) { ?>
                <button class="cms-author-picker__item" type="button" role="option"
                        data-author-option
                        data-id="<?php echo (int) $author->id; ?>"
                        data-style="<?php echo h($author->avatarStyle); ?>"
                        data-name="<?php echo h($author->name); ?>"
                        data-sub="<?php echo h($author->email); ?>"
                        aria-selected="<?php echo $author->id === $selectedId ? 'true' : 'false'; ?>">
                    <span class="cms-author-picker__avatar" style="<?php echo h($author->avatarStyle); ?>"></span>
                    <span>
                        <span class="cms-author-picker__name"><?php echo h($author->name); ?></span>
                        <span class="cms-author-picker__role"><?php echo h($author->email); ?></span>
                    </span>
                    <span class="cms-author-picker__check" data-author-check aria-hidden="true"><?php echo $author->id === $selectedId ? '✓' : ''; ?></span>
                </button>
            <?php } ?>
        </div>
    </div>
</section>
