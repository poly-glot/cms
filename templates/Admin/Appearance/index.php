<?php
/**
 * @var App\View\AppView $this
 * @var list<App\Model\Enum\SiteTheme> $themes
 * @var App\Model\Enum\SiteTheme $active
 */
$this->assign('title', 'Appearance');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Appearance', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <h1 class="cms-page-header__title">Appearance</h1>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-create">
    <?php foreach ($themes as $theme) { ?>
        <?php if ($theme === $active) { ?>
            <div class="cms-create__tile" style="cursor:default;">
                <div class="cms-theme-card__swatch" style="<?php echo h($theme->swatchStyle()); ?>"></div>
                <div class="cms-create__tile-title"><?php echo h($theme->label()); ?> <span class="cms-theme-card__active">· active</span></div>
                <div class="cms-create__tile-desc"><?php echo h($theme->description()); ?></div>
            </div>
        <?php } else { ?>
            <button type="submit" form="activate-<?php echo h($theme->value); ?>" class="cms-create__tile" style="cursor:pointer;">
                <div class="cms-theme-card__swatch" style="<?php echo h($theme->swatchStyle()); ?>"></div>
                <div class="cms-create__tile-title"><?php echo h($theme->label()); ?></div>
                <div class="cms-create__tile-desc"><?php echo h($theme->description()); ?></div>
            </button>
        <?php } ?>
    <?php } ?>
</div>

<?php foreach ($themes as $theme) { ?>
    <?php if ($theme !== $active) { ?>
        <?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/appearance/activate', 'id' => 'activate-' . $theme->value, 'hidden' => true]); ?>
            <input type="hidden" name="theme" value="<?php echo h($theme->value); ?>">
        <?php echo $this->Form->end(); ?>
    <?php } ?>
<?php } ?>
