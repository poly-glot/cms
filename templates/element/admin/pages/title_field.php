<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page $page
 */
?>
<div class="cms-page-header__title-row">
    <h1 class="cms-page-header__title" data-title-input contenteditable="true" role="textbox" aria-label="Page title" data-placeholder="Untitled page"><?php echo h($page->title ?? ''); ?></h1>
    <button type="button" class="cms-title-edit" data-title-edit>Edit</button>
</div>
