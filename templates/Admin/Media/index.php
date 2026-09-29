<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\Media> $media
 * @var string $activeKind
 * @var string $keyword
 */

use Cake\I18n\Number;

$this->assign('title', 'Media Library');

$filters = ['all' => 'All', 'image' => 'Images', 'document' => 'Documents', 'audio' => 'Audio', 'video' => 'Video'];
$filterHref = static fn (string $kind): string => '/admin/media?kind=' . $kind . ($keyword !== '' ? '&q=' . rawurlencode($keyword) : '');
?>
<?php echo $this->element('admin/breadcrumb', [
    'crumbs' => [
        ['label' => 'Home', 'url' => $workspacePath . '/admin'],
        ['label' => 'Media', 'url' => null],
    ],
]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Media Library</h1>
        <div class="cms-page-header__meta">Each image is stored with renditions — a thumbnail, small, medium, large, and original.</div>
    </div>
    <div class="cms-page-header__actions">
        <button class="cms-btn cms-btn--primary" type="button" data-upload-trigger>+ Upload</button>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-media">
    <div class="cms-media__toolbar">
        <nav class="cms-media__filters" aria-label="Filter by type">
            <?php foreach ($filters as $value => $label) { ?>
                <a
                    class="cms-media__filter<?php echo $activeKind === $value ? ' cms-media__filter--active' : ''; ?>"
                    href="<?php echo h($workspacePath . $filterHref($value)); ?>"
                ><?php echo h($label); ?></a>
            <?php } ?>
        </nav>

        <form class="cms-media__searchform" method="get" action="<?php echo h($workspacePath); ?>/admin/media">
            <input type="hidden" name="kind" value="<?php echo h($activeKind); ?>">
            <input
                class="cms-media__search"
                type="search"
                name="q"
                value="<?php echo h($keyword); ?>"
                placeholder="Search by name&hellip;"
                aria-label="Search media"
            >
            <?php if ($keyword !== '') { ?>
                <a class="cms-media__searchclear" href="<?php echo h($workspacePath . '/admin/media?kind=' . $activeKind); ?>" aria-label="Clear search" title="Clear search">&times;</a>
            <?php } ?>
        </form>

        <div class="cms-media__count" data-media-count><?php echo $this->Paginator->counter('{{count}} files'); ?></div>
    </div>

    <div class="cms-media__grid" data-media-grid aria-live="polite">
        <button class="cms-upload-tile" type="button" data-upload-trigger>
            <div>
                <div class="cms-upload-tile__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
                </div>
                <div class="cms-upload-tile__title">Upload</div>
                <div class="cms-upload-tile__desc">drag files here or click</div>
            </div>
        </button>

        <?php $hasFiles = false; ?>
        <?php foreach ($media as $item) { ?>
            <?php $hasFiles = true; ?>
            <?php
            $primary = $item->isImage && $item->width !== null && $item->height !== null
                ? $item->width . '×' . $item->height
                : strtoupper($item->extension);
            ?>
            <article
                class="cms-media-card"
                data-media-id="<?php echo (int) $item->id; ?>"
                data-kind="<?php echo h($item->kind); ?>"
                data-name="<?php echo h(mb_strtolower($item->name)); ?>"
                tabindex="0"
            >
                <div class="cms-media-card__thumb">
                    <?php if ($item->isImage) { ?>
                        <img loading="lazy" alt="<?php echo h($item->alt ?? $item->name); ?>" src="<?php echo h($workspacePath); ?>/admin/media/serve/<?php echo (int) $item->id; ?>?rendition=thumb">
                    <?php } else { ?>
                        <div class="cms-media-card__doc-icon" data-ext="<?php echo h(strtoupper($item->extension)); ?>"></div>
                    <?php } ?>
                    <span class="cms-media-card__badge"><?php echo h($item->kind); ?></span>
                </div>
                <div class="cms-media-card__meta">
                    <div class="cms-media-card__name"><?php echo h($item->name); ?></div>
                    <div class="cms-media-card__sub">
                        <span><?php echo h($primary); ?></span>
                        <span><?php echo h(Number::toReadableSize($item->size)); ?></span>
                    </div>
                </div>
            </article>
        <?php } ?>

        <?php if (!$hasFiles) { ?>
            <div class="cms-media__empty">No files match these filters.</div>
        <?php } ?>
    </div>

    <?php $this->Paginator->options(['url' => ['?' => array_diff_key($this->request->getQueryParams(), ['page' => null])]]); ?>
    <?php if ($this->Paginator->total() > 1) { ?>
        <nav class="cms-pagination" aria-label="Pages">
            <?php echo $this->Paginator->prev('‹ Prev'); ?>
            <?php echo $this->Paginator->numbers(); ?>
            <?php echo $this->Paginator->next('Next ›'); ?>
        </nav>
    <?php } ?>
</div>

<input type="file" data-upload-input multiple style="display:none" accept="image/*,application/pdf,.doc,.docx,.txt,.zip,.mp3,.wav,.mp4,.mov">

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/media.mjs'); ?>"></script>
<?php $this->end(); ?>
