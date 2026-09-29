<?php
/** @var Cake\View\View $this */
/* @var string $token */
declare(strict_types=1);

$this->assign('title', 'Set your password');
$this->assign('subheading', 'Choose a password to finish setting up your account.');
?>
<form class="auth__form" method="post"
      action="<?php echo $this->Url->build(['controller' => 'Users', 'action' => 'setPassword', $token]); ?>">
    <?php echo $this->Form->hidden('_csrfToken', ['value' => $this->request->getAttribute('csrfToken')]); ?>

    <label class="auth-field">
        <span class="auth-field__label">New password</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="password" name="password"
                   id="password" autocomplete="new-password"
                   minlength="8" required>
        </span>
    </label>

    <button class="auth-submit" type="submit">Set password</button>
</form>
