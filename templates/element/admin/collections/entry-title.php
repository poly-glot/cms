<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\CollectionEntry $entry
 */
?>
<?php $titleErrors = $entry->getError('title'); ?>
<div class="cms-page-header__title-row">
    <h1 class="cms-page-header__title<?php echo $titleErrors !== [] ? ' cms-page-header__title--invalid' : ''; ?>" data-title-input contenteditable="true" role="textbox" aria-label="Entry title" data-placeholder="Untitled entry"><?php echo h($entry->title ?? ''); ?></h1>
    <button type="button" class="cms-title-edit" data-title-edit>Edit</button>
</div>
<?php if ($titleErrors !== []) { ?>
    <p class="cms-field__error cms-page-header__title-error"><?php echo h(implode(' ', $titleErrors)); ?></p>
<?php } ?>
