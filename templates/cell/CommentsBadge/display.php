<?php
/**
 * @var App\View\AppView $this
 * @var int $count
 */
?>
<?php if ($count > 0) { ?>
<span class="cms-sidebar__badge" aria-label="<?php echo (int) $count; ?> unread"><?php echo (int) $count; ?></span>
<?php } ?>
