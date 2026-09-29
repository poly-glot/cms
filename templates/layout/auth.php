<?php
/** @var Cake\View\View $this */
declare(strict_types=1);

$title = $this->fetch('title');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo h($title); ?> — Cabinet</title>
    <?php echo $this->Html->css(['tokens', 'admin/reset', 'auth']); ?>
</head>
<body>

<main class="auth" role="main" aria-labelledby="auth-heading">

    <header class="auth__titlebar">
        <span class="auth__lights" aria-hidden="true">
            <span class="auth__light auth__light--close"></span>
            <span class="auth__light auth__light--min"></span>
            <span class="auth__light auth__light--max"></span>
        </span>
        <span class="auth__title">Cabinet — <?php echo $this->fetch('windowTitle', $title); ?></span>
    </header>

    <header class="auth__header">
        <div class="auth__brand">
            <span class="auth__brand-mark" aria-hidden="true"></span>
            Cabinet
        </div>
        <h1 class="auth__heading" id="auth-heading"><?php echo $this->fetch('heading', $title); ?></h1>
        <p class="auth__subheading"><?php echo $this->fetch('subheading'); ?></p>
        <?php echo $this->fetch('tabs'); ?>
    </header>

    <div class="auth__body">

        <div class="auth-flash">
            <?php echo $this->Flash->render(); ?>
        </div>

        <?php echo $this->fetch('content'); ?>
    </div>

    <?php echo $this->fetch('footer'); ?>

</main>

<?php echo $this->fetch('script'); ?>
</body>
</html>
