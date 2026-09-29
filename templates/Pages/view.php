<?php
/**
 * @var App\Model\Entity\Page $page
 * @var array<App\Model\Entity\Page> $ancestors
 * @var Cake\View\View $this
 */

use Cake\Utility\Text;

$ogDescription = Text::truncate(strip_tags((string) $page->body), 160);
$this->assign('title', $page->title);
$this->start('meta');
echo '<meta property="og:title" content="' . h($page->title) . '">' . "\n";
echo '    <meta property="og:type" content="article">' . "\n";
echo '    <meta property="og:description" content="' . h($ogDescription) . '">' . "\n";
echo '    <meta name="twitter:card" content="summary">' . "\n";
echo '    <meta name="twitter:title" content="' . h($page->title) . '">' . "\n";
echo '    <meta name="twitter:description" content="' . h($ogDescription) . '">' . "\n";
$this->end();
?>
<?php echo $this->element('breadcrumb', ['ancestors' => $ancestors ?? [], 'page' => $page]); ?>
<article class="page">
    <header class="page__header">
        <h1 class="page__title"><?php echo h($page->title); ?></h1>
        <?php if ($page->author !== null) { ?>
            <div class="page__meta">
                <span class="page__byline">by <?php echo h($page->author->name); ?></span>
                <span class="page__meta-divider" aria-hidden="true"></span>
                <time datetime="<?php echo h($page->modified->format('Y-m-d')); ?>" class="page__date">
                    <?php echo h($page->modified->format('j M Y')); ?>
                </time>
            </div>
        <?php } ?>
    </header>
    <?php echo $this->element('page_body', ['page' => $page]); ?>
</article>
<?php echo $this->element('comments', ['page' => $page, 'comments' => $comments ?? [], 'allowComments' => $allowComments ?? true]); ?>
