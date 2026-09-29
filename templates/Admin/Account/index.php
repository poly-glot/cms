<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\User $user
 * @var string $workspacePath
 */
$this->assign('title', 'Your account');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Your account', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Your account</h1>
        <div class="cms-page-header__meta">Update your details and change your password.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-account">
    <?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/account', 'class' => 'cms-block-form cms-account__panel']); ?>
        <h2 class="cms-account__title">Profile</h2>

        <label class="cms-field">Name
            <input type="text" name="name" value="<?php echo h($user->name ?? ''); ?>" maxlength="120" required autocomplete="name">
        </label>
        <?php if ($user->getError('name') !== []) { ?>
            <p class="cms-field__error"><?php echo h(implode(' ', $user->getError('name'))); ?></p>
        <?php } ?>

        <label class="cms-field">Email
            <input type="email" name="email" value="<?php echo h($user->email ?? ''); ?>" maxlength="190" required autocomplete="email">
        </label>
        <?php if ($user->getError('email') !== []) { ?>
            <p class="cms-field__error"><?php echo h(implode(' ', $user->getError('email'))); ?></p>
        <?php } ?>

        <label class="cms-field">Current password
            <input type="password" name="current_password" autocomplete="current-password">
        </label>
        <p class="cms-field__hint">Only needed when you change your email address.</p>

        <div class="cms-block-form__actions">
            <button type="submit" class="cms-btn cms-btn--primary">Save profile</button>
        </div>
    <?php echo $this->Form->end(); ?>

    <?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/account/password', 'class' => 'cms-block-form cms-account__panel']); ?>
        <h2 class="cms-account__title">Password</h2>

        <label class="cms-field">Current password
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>

        <label class="cms-field">New password
            <input type="password" name="new_password" minlength="8" autocomplete="new-password" required>
        </label>

        <label class="cms-field">Confirm new password
            <input type="password" name="new_password_confirm" minlength="8" autocomplete="new-password" required>
        </label>

        <p class="cms-field__hint">Use at least eight characters. You will stay signed in after changing it.</p>

        <div class="cms-block-form__actions">
            <button type="submit" class="cms-btn cms-btn--primary">Change password</button>
        </div>
    <?php echo $this->Form->end(); ?>
</div>
