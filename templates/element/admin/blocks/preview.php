<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Block $block
 * @var App\Model\Entity\Media|null $media
 */

use App\Model\Enum\BlockType;
use Cake\I18n\Number;

$media = $media ?? null;
$type = $block->type;
$data = $block->data;

$modifier = match ($type) {
    BlockType::Statistic => 'stat',
    default => $type->value,
};
$chip = $type->chipLabel();

$value = static fn (string $key): string => h((string) ($data[$key] ?? ''));
$has = static fn (string $key): bool => ($data[$key] ?? '') !== '';
$mediaSrc = $media !== null ? $workspacePath . '/admin/media/serve/' . (int) $media->id . '?rendition=large' : '';
$mediaExt = $media !== null ? strtoupper(pathinfo((string) $media->name, \PATHINFO_EXTENSION)) : '';
$mediaName = $media !== null ? (string) $media->name : '';
$mediaMeta = $media !== null ? $mediaName . ' · ' . Number::toReadableSize($media->size) : '';
?>
<div class="cms-content-block cms-content-block--<?php echo h($modifier); ?>" data-preview-root<?php echo $type->referencesMedia() && $media === null ? ' data-empty' : ''; ?>>
    <span class="cms-content-block__chip"><?php echo h($chip); ?></span>

    <?php if ($type === BlockType::Callout) { ?>
        <h3 class="cms-content-block__title" data-preview-bind="name"><?php echo h($block->name); ?></h3>
        <p class="cms-content-block__body" data-preview-bind="data[body]"><?php echo $value('body'); ?></p>

    <?php } elseif ($type === BlockType::Quote) { ?>
        <p class="cms-content-block__text" data-preview-bind="data[text]"><?php echo $value('text'); ?></p>
        <div class="cms-content-block__attr" data-preview-optional <?php echo $has('attribution') ? '' : 'hidden'; ?>>— <span data-preview-bind="data[attribution]"><?php echo $value('attribution'); ?></span></div>

    <?php } elseif ($type === BlockType::Statistic) { ?>
        <div class="cms-content-block__value" data-preview-bind="data[value]"><?php echo $value('value'); ?></div>
        <div>
            <p class="cms-content-block__label" data-preview-bind="data[label]"><?php echo $value('label'); ?></p>
            <p class="cms-content-block__sub" data-preview-bind="data[caption]" data-preview-optional <?php echo $has('caption') ? '' : 'hidden'; ?>><?php echo $value('caption'); ?></p>
        </div>

    <?php } elseif ($type === BlockType::Cta) { ?>
        <div>
            <h3 class="cms-content-block__heading" data-preview-bind="name"><?php echo h($block->name); ?></h3>
        </div>
        <span class="cms-btn cms-content-block__button" data-preview-bind="data[label]"><?php echo $value('label'); ?></span>

    <?php } elseif ($type === BlockType::Image) { ?>
        <div class="cms-content-block__placeholder" data-preview-empty>No image selected yet.</div>
        <figure class="cms-content-block__figure" data-preview-media>
            <img src="<?php echo h($mediaSrc); ?>" alt="<?php echo $value('alt'); ?>" data-preview-img loading="lazy">
            <figcaption data-preview-bind="data[caption]" data-preview-optional <?php echo $has('caption') ? '' : 'hidden'; ?>><?php echo $value('caption'); ?></figcaption>
        </figure>

    <?php } elseif ($type === BlockType::File) { ?>
        <div class="cms-content-block__placeholder" data-preview-empty style="grid-column:1/-1;">No file selected yet.</div>
        <div class="cms-content-block__file-icon" data-preview-media>
            <span class="cms-content-block__file-ext" data-preview-ext><?php echo h($mediaExt); ?></span>
        </div>
        <div data-preview-media>
            <p class="cms-content-block__file-title" data-preview-bind="data[label]" data-preview-fallback="<?php echo h($mediaName); ?>"><?php echo $has('label') ? $value('label') : h($mediaName); ?></p>
            <p class="cms-content-block__file-meta" data-preview-meta><?php echo h($mediaMeta); ?></p>
        </div>
        <span class="cms-btn cms-btn--primary" data-preview-media>Download</span>
    <?php } ?>
</div>
