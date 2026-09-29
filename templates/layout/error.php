<?php
/**
 * @var App\View\AppView $this
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo h($this->fetch('title')); ?> — Cabinet</title>
    <?php echo $this->Html->css('heritage'); ?>
    <style>
        .err { display: grid; place-items: center; min-height: 100vh; padding: 24px; text-align: center; }
        .err__code { font-family: "Lora", Georgia, serif; font-size: 96px; font-weight: 600; color: #2a2520; margin: 0; line-height: 1; }
        .err__msg { font-family: "Lora", Georgia, serif; font-size: 28px; color: #5b5247; margin: 16px 0 8px; }
        .err__hint { color: #8e8576; margin: 0 0 24px; }
        .err__home { display: inline-block; padding: 10px 18px; border: 1px solid #b8ab92; border-radius: 6px; color: #2a2520; text-decoration: none; }
    </style>
</head>
<body>
    <main class="err">
        <?php echo $this->fetch('content'); ?>
    </main>
</body>
</html>
