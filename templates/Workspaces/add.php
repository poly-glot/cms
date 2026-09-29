<?php
/**
 * @var App\View\AppView $this
 * @var array{name: string, slug: string}|null $form
 * @var array<string, array<string, string>>|null $errors
 */
declare(strict_types=1);

$this->assign('title', 'Create a new workspace');
$this->assign('windowTitle', 'New workspace');
$this->assign('subheading', 'A fresh site, owned by your account. You stay signed in.');

$form = $form ?? ['name' => '', 'slug' => ''];
$errors = $errors ?? [];

$fieldError = static function (array $errors, string $field): string {
    $messages = $errors[$field] ?? [];
    if (!is_array($messages) || $messages === []) {
        return '';
    }

    return (string) reset($messages);
};
?>
<form class="auth__form" method="post" action="<?php echo $this->Url->build('/workspaces/new'); ?>">
    <?php echo $this->Form->hidden('_csrfToken', ['value' => $this->request->getAttribute('csrfToken')]); ?>

    <label class="auth-field">
        <span class="auth-field__label">Workspace name</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="text" name="name"
                   value="<?php echo h($form['name']); ?>"
                   placeholder="Acme Studio" required data-workspace-name>
        </span>
        <?php $err = $fieldError($errors, 'name');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <label class="auth-field">
        <span class="auth-field__label">Workspace URL</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="text" name="slug"
                   value="<?php echo h($form['slug']); ?>"
                   pattern="[a-z0-9\-]+" placeholder="acme-studio" required data-workspace-slug>
        </span>
        <span class="auth-field__hint-text">Lowercase letters, numbers and hyphens. Your site lives at /your-slug/.</span>
        <?php $err = $fieldError($errors, 'slug');
if ($err !== '') { ?>
            <span class="auth-field__error"><?php echo h($err); ?></span>
        <?php } ?>
    </label>

    <button class="auth-submit" type="submit">Create workspace</button>
</form>

<?php $this->start('footer'); ?>
<footer class="auth__footer">
    <a href="/" class="auth__footer-link">Back to your workspace</a>
</footer>
<?php $this->end(); ?>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/workspace/new.mjs'); ?>"></script>
<?php $this->end(); ?>
