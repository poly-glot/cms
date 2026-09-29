<?php
/**
 * @var Cake\View\View $this
 * @var array|Authentication\IdentityInterface|null $identity
 * @var string $workspacePath
 */
?>
<header class="cms-topbar">
    <a class="cms-topbar__brand" href="<?php echo h($workspacePath); ?>/admin">
        <span class="cms-topbar__brand-mark" aria-hidden="true"></span>
        <span class="cms-topbar__brand-text">Cabinet</span>
    </a>
    <div class="cms-topbar__welcome">Welcome, <?php echo h($identity?->name ?? 'Admin'); ?></div>
    <?php echo $this->cell('WorkspaceSwitcher'); ?>
    <nav class="cms-topbar__nav" aria-label="Account">
        <a class="cms-topbar__link cms-topbar__link--profile" href="<?php echo h($workspacePath); ?>/admin/account">
            <span aria-hidden="true"></span>My Profile
        </a>
        <?php echo $this->Form->postLink('Logout', ['controller' => 'Users', 'action' => 'logout', 'prefix' => false], [
            'class' => 'cms-topbar__link',
        ]); ?>
    </nav>
</header>
