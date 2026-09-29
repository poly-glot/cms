<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Collection $collection
 */
?>
<div class="cms-page-header__title-row">
    <h1 class="cms-page-header__title" data-title-input data-title-target="name" contenteditable="true" role="textbox" aria-label="Collection name" data-placeholder="Untitled collection"><?php echo h($collection->name ?? ''); ?></h1>
    <button type="button" class="cms-title-edit" data-title-edit>Edit</button>
</div>
