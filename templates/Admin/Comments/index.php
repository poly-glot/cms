<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\Comment> $comments
 */
$this->assign('title', 'Comments');

$commentList = iterator_to_array($comments);
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Comments', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <h1 class="cms-page-header__title">Comments</h1>
    <div class="cms-page-header__actions">
        <button class="cms-btn" type="button" data-comments-mark-read>Mark all read</button>
        <button class="cms-btn cms-btn--primary" type="button" data-comments-approve-selected>Approve selected</button>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-comments-list" data-comments>
    <?php if ($commentList === []) { ?>
        <div class="cms-comments__empty">No comments yet. When visitors comment on your pages, they'll appear here for moderation.</div>
    <?php } ?>
    <?php foreach ($commentList as $comment) {
        $isPending = $comment->status === 'pending';
        $isApproved = $comment->status === 'approved';
        ?>
        <article
            class="cms-comment<?php echo $comment->is_read ? '' : ' cms-comment--unread'; ?>"
            data-comment-id="<?php echo (int) $comment->id; ?>"
        >
            <div class="cms-comment__avatar" style="<?php echo h($comment->avatarStyle); ?>" aria-hidden="true"></div>
            <div class="cms-comment__main">
                <div class="cms-comment__head">
                    <span class="cms-comment__author"><?php echo h($comment->author_name); ?></span>
                    <span class="cms-comment__when">on &ldquo;<?php echo h($comment->page?->title ?? $comment->post?->title ?? 'a page'); ?>&rdquo; &middot; <?php echo h($comment->created->timeAgoInWords()); ?></span>
                    <?php if (!$isPending && !$isApproved) { ?>
                        <span class="cms-comment__status cms-comment__status--<?php echo h($comment->status); ?>"><?php echo h(ucfirst($comment->status)); ?></span>
                    <?php } ?>
                </div>
                <p class="cms-comment__body"><?php echo h($comment->body); ?></p>

                <?php foreach ($comment->children as $reply) { ?>
                    <div class="cms-comment__reply-item">
                        <span class="cms-comment__author"><?php echo h($reply->author_name); ?></span>
                        <span class="cms-comment__when"><?php echo h($reply->created->timeAgoInWords()); ?></span>
                        <p class="cms-comment__body"><?php echo h($reply->body); ?></p>
                    </div>
                <?php } ?>

                <form class="cms-comment__reply-form" data-reply-form hidden>
                    <textarea rows="2" placeholder="Write a reply…" data-reply-body></textarea>
                    <div class="cms-comment__reply-actions">
                        <button type="button" class="cms-btn cms-btn--primary" data-reply-submit>Post reply</button>
                        <button type="button" class="cms-btn" data-reply-cancel>Cancel</button>
                    </div>
                </form>
            </div>
            <div class="cms-comment__actions">
                <?php if ($isPending) { ?>
                    <input type="checkbox" class="cms-comment__check" data-comment-check value="<?php echo (int) $comment->id; ?>" aria-label="Select for bulk approve">
                <?php } ?>
                <button class="cms-btn" type="button" data-reply-toggle>Reply</button>
                <?php if ($isPending) { ?>
                    <button class="cms-btn" type="button" data-comment-action="spam">Spam</button>
                    <button class="cms-btn cms-btn--primary" type="button" data-comment-action="approve">Approve</button>
                <?php } elseif ($isApproved) { ?>
                    <button class="cms-btn" type="button" data-comment-action="archive">Archive</button>
                <?php } else { ?>
                    <button class="cms-btn" type="button" data-comment-action="approve">Approve</button>
                    <button class="cms-btn cms-btn--danger" type="button" data-comment-action="delete">Delete</button>
                <?php } ?>
            </div>
        </article>
    <?php } ?>
</div>

<?php if ($this->Paginator->total() > 1) { ?>
    <nav class="cms-pagination" aria-label="Pages">
        <?php echo $this->Paginator->prev('‹ Newer'); ?>
        <?php echo $this->Paginator->numbers(); ?>
        <?php echo $this->Paginator->next('Older ›'); ?>
    </nav>
<?php } ?>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/comments.mjs'); ?>"></script>
<?php $this->end(); ?>
