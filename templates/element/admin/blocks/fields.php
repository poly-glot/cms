<?php
/**
 * Per-type block data inputs.
 *
 * @var App\View\AppView $this
 * @var string $type
 * @var array<string, mixed> $data
 */
$value = static fn (string $key): string => h((string) ($data[$key] ?? ''));
$selected = static fn (string $key, string $option): string => ($data[$key] ?? '') === $option ? 'selected' : '';
?>
<?php if ($type === 'callout') { ?>
    <label class="cms-field">Variant
        <select name="data[variant]">
            <?php foreach (['info', 'warning', 'success', 'note'] as $variant) { ?>
                <option value="<?php echo $variant; ?>" <?php echo $selected('variant', $variant); ?>><?php echo ucfirst($variant); ?></option>
            <?php } ?>
        </select>
    </label>
    <label class="cms-field">Body
        <textarea name="data[body]" rows="3" required><?php echo $value('body'); ?></textarea>
    </label>
<?php } elseif ($type === 'quote') { ?>
    <label class="cms-field">Quote
        <textarea name="data[text]" rows="3" required><?php echo $value('text'); ?></textarea>
    </label>
    <label class="cms-field">Attribution
        <input type="text" name="data[attribution]" value="<?php echo $value('attribution'); ?>">
    </label>
<?php } elseif ($type === 'statistic') { ?>
    <label class="cms-field">Value
        <input type="text" name="data[value]" value="<?php echo $value('value'); ?>" required>
    </label>
    <label class="cms-field">Label
        <input type="text" name="data[label]" value="<?php echo $value('label'); ?>" required>
    </label>
    <label class="cms-field">Caption
        <input type="text" name="data[caption]" value="<?php echo $value('caption'); ?>">
    </label>
<?php } elseif ($type === 'cta') { ?>
    <label class="cms-field">Label
        <input type="text" name="data[label]" value="<?php echo $value('label'); ?>" required>
    </label>
    <label class="cms-field">URL
        <input type="text" name="data[url]" value="<?php echo $value('url'); ?>" required>
        <span class="cms-field__hint">An https:// link, a mailto: address, or a /page path on this site.</span>
    </label>
    <label class="cms-field">Style
        <select name="data[style]">
            <?php foreach (['primary', 'secondary'] as $style) { ?>
                <option value="<?php echo $style; ?>" <?php echo $selected('style', $style); ?>><?php echo ucfirst($style); ?></option>
            <?php } ?>
        </select>
    </label>
<?php } elseif ($type === 'image') { ?>
    <div class="cms-field" data-media-slot data-kind="image">
        Image
        <input type="hidden" name="data[media_id]" value="<?php echo $value('media_id'); ?>" data-slot-input>
        <div class="cms-media-slot">
            <div class="cms-media-slot__thumb" data-slot-thumb>No image</div>
            <div class="cms-media-slot__info">
                <div class="cms-media-slot__name" data-slot-name>No image selected</div>
                <div class="cms-media-slot__sub" data-slot-sub></div>
            </div>
            <button type="button" class="cms-btn" data-slot-choose>Choose image</button>
        </div>
    </div>
    <label class="cms-field">Rendition
        <select name="data[rendition]">
            <?php foreach (['large' => 'Large', 'medium' => 'Medium', 'small' => 'Small', 'thumb' => 'Thumbnail'] as $rendition => $label) { ?>
                <option value="<?php echo $rendition; ?>" <?php echo ($data['rendition'] ?? 'large') === $rendition ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php } ?>
        </select>
    </label>
    <label class="cms-field">Alt text
        <input type="text" name="data[alt]" value="<?php echo $value('alt'); ?>">
        <span class="cms-field__hint">Describes the image for screen readers and when it can't load.</span>
    </label>
    <label class="cms-field">Caption
        <input type="text" name="data[caption]" value="<?php echo $value('caption'); ?>">
    </label>
<?php } elseif ($type === 'file') { ?>
    <div class="cms-field" data-media-slot data-kind="document">
        File
        <input type="hidden" name="data[media_id]" value="<?php echo $value('media_id'); ?>" data-slot-input>
        <div class="cms-media-slot">
            <div class="cms-media-slot__thumb" data-slot-thumb>No file</div>
            <div class="cms-media-slot__info">
                <div class="cms-media-slot__name" data-slot-name>No file selected</div>
                <div class="cms-media-slot__sub" data-slot-sub></div>
            </div>
            <button type="button" class="cms-btn" data-slot-choose>Choose file</button>
        </div>
    </div>
    <label class="cms-field">Label
        <input type="text" name="data[label]" value="<?php echo $value('label'); ?>">
    </label>
<?php } ?>
