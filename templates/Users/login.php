<?php
/** @var Cake\View\View $this */
declare(strict_types=1);

$this->assign('title', 'Sign in');
$this->assign('heading', 'Welcome back');
$this->assign('subheading', 'Sign in to manage your shop.');
?>
<?php $this->start('tabs'); ?>
<nav class="auth__tabs">
    <span class="auth__tab auth__tab--active" aria-current="page">Sign in</span>
    <a class="auth__tab"
       href="<?php echo $this->Url->build(['controller' => 'Signup', 'action' => 'index']); ?>">Create account</a>
</nav>
<?php $this->end(); ?>

<form class="auth__form" id="signin-form"
      method="post" action="<?php echo $this->Url->build(['controller' => 'Users', 'action' => 'login']); ?>">
    <?php echo $this->Form->hidden('_csrfToken', ['value' => $this->request->getAttribute('csrfToken')]); ?>

    <label class="auth-field">
        <span class="auth-field__label">Email</span>
        <span class="auth-field__input-wrap">
            <span class="auth-field__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3.5" width="12" height="9" rx="1.5"/><path d="M2.5 4.5l5.5 4 5.5-4"/></svg>
            </span>
            <input class="auth-field__input" type="email" name="email"
                   id="email" autocomplete="email"
                   placeholder="you@workshop.co" required>
        </span>
    </label>

    <label class="auth-field">
        <span class="auth-field__label">
            <span>Password</span>
            <a href="<?php echo $this->Url->build(['controller' => 'Users', 'action' => 'forgotPassword']); ?>" class="auth-field__hint">Forgot?</a>
        </span>
        <span class="auth-field__input-wrap">
            <span class="auth-field__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/></svg>
            </span>
            <input class="auth-field__input" type="password" name="password"
                   id="password" autocomplete="current-password"
                   placeholder="Your password" required>
            <button type="button" class="auth-field__suffix"
                    data-toggle-password
                    aria-pressed="false"
                    aria-label="Show password">Show</button>
        </span>
    </label>

    <button class="auth-submit" type="submit">Sign in</button>
</form>

<?php $this->start('footer'); ?>
<footer class="auth__footer">
    <span>New to Cabinet?</span>
    <a href="<?php echo $this->Url->build(['controller' => 'Signup', 'action' => 'index']); ?>"
       class="auth__footer-link">Create an account</a>
</footer>
<?php $this->end(); ?>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/auth/login.mjs'); ?>"></script>
<?php $this->end(); ?>
