<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\User $user
 * @var list<App\Model\Enum\UserRole> $roles
 */
$this->assign('title', 'Invite a user');
$submittedRole = $this->getRequest()->getData('role');
$selectedRole = is_string($submittedRole) ? $submittedRole : 'author';
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Users', 'url' => $workspacePath . '/admin/users'],
    ['label' => 'Invite', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <h1 class="cms-page-header__title">Invite a user</h1>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/users/invite', 'class' => 'cms-block-form cms-panel']); ?>

    <label class="cms-field">Name
        <input type="text" name="name" value="<?php echo h($user->name ?? ''); ?>" maxlength="120" required>
    </label>
    <?php if ($user->getError('name') !== []) { ?>
        <p class="cms-field__error"><?php echo h(implode(' ', $user->getError('name'))); ?></p>
    <?php } ?>

    <label class="cms-field">Email
        <input type="email" name="email" value="<?php echo h($user->email ?? ''); ?>" maxlength="190" required>
    </label>
    <?php if ($user->getError('email') !== []) { ?>
        <p class="cms-field__error"><?php echo h(implode(' ', $user->getError('email'))); ?></p>
    <?php } ?>

    <label class="cms-field">Role
        <select name="role">
            <?php foreach ($roles as $role) { ?>
                <option value="<?php echo h($role->value); ?>" <?php echo $selectedRole === $role->value ? 'selected' : ''; ?>>
                    <?php echo h($role->label()); ?>
                </option>
            <?php } ?>
        </select>
    </label>

    <p class="cms-field__hint">An invitation email with a one-time link will be sent. The recipient sets their own password.</p>

    <div class="cms-block-form__actions">
        <button type="submit" class="cms-btn cms-btn--primary">Send invite</button>
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/users">Cancel</a>
    </div>
<?php echo $this->Form->end(); ?>
