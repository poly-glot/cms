<?php
/**
 * @var App\View\AppView $this
 * @var array<string, string> $settings
 */
$this->assign('title', 'Settings');

$toggles = [
    'allow_comments' => ['Allow comments', 'Show a comment box at the bottom of public pages.'],
    'moderation_queue' => ['Moderation queue', 'Hold new comments for review before they appear.'],
];
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Settings', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <h1 class="cms-page-header__title">Settings</h1>
    <div class="cms-page-header__actions">
        <button type="submit" form="settings-form" class="cms-btn cms-btn--primary">Save changes</button>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<form id="settings-form" method="post" action="<?= h($workspacePath) ?>/admin/settings/save" class="cms-settings">
    <?php echo $this->Form->hidden('_csrfToken', ['value' => $this->getRequest()->getAttribute('csrfToken')]); ?>

    <section class="cms-card">
        <header class="cms-card__header"><span>General</span></header>
        <div class="cms-card__body">
            <div class="cms-setting-row">
                <div>
                    <div class="cms-setting__name">Site title</div>
                    <div class="cms-setting__desc">Shown in the browser tab and search results.</div>
                </div>
                <input class="cms-setting__input" type="text" name="site_title" maxlength="120" value="<?php echo h($settings['site_title'] ?? ''); ?>">
            </div>
            <div class="cms-setting-row">
                <div>
                    <div class="cms-setting__name">Tagline</div>
                    <div class="cms-setting__desc">A short line for sharing previews.</div>
                </div>
                <input class="cms-setting__input" type="text" name="tagline" maxlength="200" value="<?php echo h($settings['tagline'] ?? ''); ?>">
            </div>
        </div>
    </section>

    <section class="cms-card">
        <header class="cms-card__header"><span>Publishing</span></header>
        <div class="cms-card__body">
            <?php foreach ($toggles as $key => [$name, $desc]) {
                $on = ($settings[$key] ?? '0') === '1';
                ?>
                <div class="cms-setting-row">
                    <div>
                        <div class="cms-setting__name"><?php echo h($name); ?></div>
                        <div class="cms-setting__desc"><?php echo h($desc); ?></div>
                    </div>
                    <span class="cms-toggle-control">
                        <input type="hidden" name="<?php echo h($key); ?>" value="0">
                        <input type="checkbox" class="cms-toggle" role="switch" name="<?php echo h($key); ?>" value="1" <?php echo $on ? 'checked' : ''; ?> aria-label="Toggle <?php echo h($name); ?>">
                    </span>
                </div>
            <?php } ?>
        </div>
    </section>
</form>
