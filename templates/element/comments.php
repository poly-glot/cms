<?php
/**
 * Public comments thread + submission form.
 *
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page $page
 * @var iterable<App\Model\Entity\Comment> $comments
 * @var bool $allowComments
 */
$list = is_array($comments) ? $comments : iterator_to_array($comments);
$allowComments = $allowComments ?? true;
?>
<section class="comments" id="comments" aria-labelledby="comments-title">
    <h2 class="comments__title" id="comments-title">Comments</h2>

    <?php echo $this->Flash->render(); ?>

    <?php if ($list === []) { ?>
        <p class="comments__empty">No comments yet — be the first to share your thoughts.</p>
    <?php } else { ?>
        <ol class="comments__list">
            <?php foreach ($list as $comment) { ?>
                <li class="comment">
                    <div class="comment__head">
                        <span class="comment__author"><?php echo h($comment->author_name); ?></span>
                        <time class="comment__date" datetime="<?php echo h($comment->created->format(\DATE_ATOM)); ?>"><?php echo h($comment->created->format('j M Y')); ?></time>
                    </div>
                    <p class="comment__body"><?php echo nl2br(h($comment->body)); ?></p>
                    <?php foreach ($comment->children as $reply) { ?>
                        <div class="comment comment--reply">
                            <div class="comment__head">
                                <span class="comment__author"><?php echo h($reply->author_name); ?></span>
                                <time class="comment__date" datetime="<?php echo h($reply->created->format(\DATE_ATOM)); ?>"><?php echo h($reply->created->format('j M Y')); ?></time>
                            </div>
                            <p class="comment__body"><?php echo nl2br(h($reply->body)); ?></p>
                        </div>
                    <?php } ?>
                </li>
            <?php } ?>
        </ol>
    <?php } ?>

    <?php if ($allowComments) { ?>
    <h3 class="comments__form-title">Leave a comment</h3>
    <?php echo $this->Form->create(null, ['url' => '/comments', 'class' => 'comments__form']); ?>
        <input type="hidden" name="page_id" value="<?php echo (int) $page->id; ?>">
        <label class="comments__field">Name
            <input type="text" name="author_name" maxlength="120" required>
        </label>
        <label class="comments__field">Email <span class="comments__optional">(optional, never published)</span>
            <input type="email" name="author_email" maxlength="255">
        </label>
        <label class="comments__field">Comment
            <textarea name="body" rows="4" required></textarea>
        </label>
        <button type="submit" class="comments__submit">Post comment</button>
    <?php echo $this->Form->end(); ?>
    <?php } ?>
</section>
