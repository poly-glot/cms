<?php
/** @var Cake\View\View $this */
declare(strict_types=1);

$this->assign('title', 'Forgot your password');
$this->assign('subheading', "Enter your email and we'll send you a reset link.");
?>
<form class="auth__form" method="post"
      action="<?php echo $this->Url->build(['controller' => 'Users', 'action' => 'forgotPassword']); ?>">
    <?php echo $this->Form->hidden('_csrfToken', ['value' => $this->request->getAttribute('csrfToken')]); ?>

    <label class="auth-field">
        <span class="auth-field__label">Email</span>
        <span class="auth-field__input-wrap">
            <input class="auth-field__input" type="email" name="email"
                   id="email" autocomplete="email"
                   placeholder="you@workshop.co" required>
        </span>
    </label>

    <button class="auth-submit" type="submit">Send reset link</button>
</form>
