<?php
/**
 * @var Cake\View\View $this
 * @var array{email: string, password: string, name: string, workspaceName: string, workspaceSlug: string}|null $form
 * @var array<string, array<string, string>>|null $errors
 */
declare(strict_types=1);

$this->assign('title', 'Create your workspace');
$this->assign('windowTitle', 'Create workspace');
$this->assign('subheading', 'Spin up a new Cabinet site in under a minute.');

$form = $form ?? ['email' => '', 'password' => '', 'name' => '', 'workspaceName' => '', 'workspaceSlug' => ''];
$errors = $errors ?? [];

$fieldError = static function (array $errors, string $scope, string $field): string {
    $scopedErrors = $errors[$scope] ?? [];
    if (!is_array($scopedErrors)) {
        return '';
    }
    $messages = $scopedErrors[$field] ?? [];
    if (!is_array($messages) || $messages === []) {
        return '';
    }

    return (string) reset($messages);
};
?>
<form class="auth__form" method="post"
      action="<?php echo $this->Url->build(['controller' => 'Signup', 'action' => 'index']); ?>">
    <?php echo $this->Form->hidden('_csrfToken', ['value' => $this->request->getAttribute('csrfToken')]); ?>

    <label class="auth-field">
        <span class="auth-field__label">Your name</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="text" name="name"
                   value="<?php echo h($form['name']); ?>" required>
        </span>
        <?php $err = $fieldError($errors, 'user', 'name');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <label class="auth-field">
        <span class="auth-field__label">Email</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="email" name="email"
                   value="<?php echo h($form['email']); ?>"
                   autocomplete="email" placeholder="you@workshop.co" required>
        </span>
        <?php $err = $fieldError($errors, 'user', 'email');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <label class="auth-field">
        <span class="auth-field__label">Password</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="password" name="password"
                   autocomplete="new-password" minlength="8" required>
        </span>
        <?php $err = $fieldError($errors, 'user', 'password');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <label class="auth-field">
        <span class="auth-field__label">Workspace name</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="text" name="workspace_name"
                   value="<?php echo h($form['workspaceName']); ?>"
                   placeholder="Acme Studio" required>
        </span>
        <?php $err = $fieldError($errors, 'workspace', 'name');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <label class="auth-field">
        <span class="auth-field__label">Workspace URL</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="text" name="workspace_slug"
                   value="<?php echo h($form['workspaceSlug']); ?>"
                   pattern="[a-z0-9\-]+" placeholder="acme-studio" required>
        </span>
        <span class="auth-field__hint-text">Lowercase letters, numbers and hyphens. Your site lives at /your-slug/.</span>
        <?php $err = $fieldError($errors, 'workspace', 'slug');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <button class="auth-submit" type="submit">Create workspace</button>
</form>

<?php $this->start('footer'); ?>
<footer class="auth__footer">
    <span>Already have an account?</span>
    <a href="<?php echo $this->Url->build(['controller' => 'Users', 'action' => 'login']); ?>"
       class="auth__footer-link">Sign in</a>
</footer>
<?php $this->end(); ?>
