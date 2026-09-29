<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Post $post
 */

use Cake\Utility\Text;

$ogDescription = $post->excerpt !== null && $post->excerpt !== ''
    ? $post->excerpt
    : Text::truncate(strip_tags((string) $post->body), 160);
$displayDate = $post->published_at ?? $post->modified;
$this->assign('title', $post->title);
$this->start('meta');
echo '<meta property="og:title" content="' . h($post->title) . '">' . "\n";
echo '    <meta property="og:type" content="article">' . "\n";
echo '    <meta property="og:description" content="' . h($ogDescription) . '">' . "\n";
echo '    <meta name="twitter:card" content="summary">' . "\n";
echo '    <meta name="twitter:title" content="' . h($post->title) . '">' . "\n";
echo '    <meta name="twitter:description" content="' . h($ogDescription) . '">' . "\n";
$this->end();
?>
<article class="page">
    <header class="page__header">
        <h1 class="page__title"><?php echo h($post->title); ?></h1>
        <div class="page__meta">
            <?php if ($post->author !== null) { ?>
                <span class="page__byline">by <?php echo h($post->author->name); ?></span>
                <span class="page__meta-divider" aria-hidden="true"></span>
            <?php } ?>
            <time datetime="<?php echo h($displayDate->format('Y-m-d')); ?>" class="page__date">
                <?php echo h($displayDate->format('j M Y')); ?>
            </time>
        </div>
    </header>
    <div class="page__body prose">
        <?php echo $this->Block->expand($post->body); ?>
    </div>
</article>
