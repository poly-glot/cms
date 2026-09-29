<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\Post> $posts
 * @var string $workspacePath
 */
$this->assign('title', 'Blog');
$items = $posts->toArray();
?>
<section class="page">
    <header class="page__header">
        <h1 class="page__title">Blog</h1>
    </header>

    <?php if ($items === []) { ?>
        <p class="page__body prose">No posts yet.</p>
    <?php } else { ?>
        <ul class="post-feed">
            <?php foreach ($items as $post) { ?>
                <?php $displayDate = $post->published_at ?? $post->created; ?>
                <li class="post-feed__item">
                    <h2 class="post-feed__title">
                        <a href="<?php echo h($workspacePath); ?>/blog/<?php echo h($post->slug); ?>"><?php echo h($post->title); ?></a>
                    </h2>
                    <div class="post-feed__meta">
                        <?php if ($post->author !== null) { ?>
                            by <?php echo h($post->author->name); ?> ·
                        <?php } ?>
                        <time datetime="<?php echo h($displayDate->format('Y-m-d')); ?>"><?php echo h($displayDate->format('j M Y')); ?></time>
                    </div>
                    <?php if ($post->excerpt !== null && $post->excerpt !== '') { ?>
                        <p class="post-feed__excerpt"><?php echo h($post->excerpt); ?></p>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>

        <?php if ($this->Paginator->total() > 1) { ?>
            <nav class="post-pagination" aria-label="Pages">
                <?php echo $this->Paginator->prev('‹ Newer'); ?>
                <?php echo $this->Paginator->numbers(); ?>
                <?php echo $this->Paginator->next('Older ›'); ?>
            </nav>
        <?php } ?>
    <?php } ?>
</section>
