<?php
/**
 * @var App\View\AppView $this
 * @var list<array{slug: string, label: string}> $selectedTags
 * @var string $tagMode
 */
$mode = ($tagMode ?? 'all') === 'any' ? 'any' : 'all';
?>
<div class="cms-filter__field cms-filter__field--full">
    <span class="cms-filter__label">Tags</span>

    <div class="cms-tag-filter" data-tag-filter data-tag-source="<?php echo h($workspacePath); ?>/admin/tags">
        <div class="cms-tag-filter__control">
            <span class="cms-tag-filter__chips" data-tag-chips>
                <?php foreach ($selectedTags ?? [] as $tag) { ?>
                    <span class="cms-chip" data-tag-chip data-slug="<?php echo h($tag['slug']); ?>">
                        <input type="hidden" name="tags[]" value="<?php echo h($tag['slug']); ?>">
                        <span class="cms-chip__label"><?php echo h($tag['label']); ?></span>
                        <button type="button" class="cms-chip__remove" data-tag-remove aria-label="Remove tag">&times;</button>
                    </span>
                <?php } ?>
            </span>
            <input type="text" class="cms-tag-filter__input" data-tag-input placeholder="Type to search tags&hellip;" autocomplete="off">
        </div>

        <div class="cms-tag-suggest" data-tag-suggest hidden></div>
    </div>

    <label class="cms-tag-filter__mode" data-tag-mode <?php echo count($selectedTags ?? []) < 2 ? 'hidden' : ''; ?>>Match
        <select name="tag_mode">
            <option value="all" <?php echo $mode === 'all' ? 'selected' : ''; ?>>All of these tags</option>
            <option value="any" <?php echo $mode === 'any' ? 'selected' : ''; ?>>Any of these tags</option>
        </select>
    </label>
</div>
