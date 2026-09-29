<?php
/**
 * Shared page body wrapper: the single place block references are expanded
 * into semantic HTML for public output.
 *
 * @var App\View\AppView $this
 * @var App\Model\Entity\Page $page
 * @var string $modifier
 */
$modifier ??= '';
?>
<div class="page__body prose<?php echo $modifier !== '' ? ' ' . h($modifier) : ''; ?>">
    <?php echo $this->Block->expand($page->body); ?>
</div>
