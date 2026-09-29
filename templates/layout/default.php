<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo h($this->fetch('title')); ?> — <?php echo h($siteTitle ?? 'Cabinet'); ?></title>
    <?php echo $this->fetch('meta'); ?>
    <?php echo $this->Html->css(isset($siteTheme) ? $siteTheme->stylesheets() : 'heritage'); ?>
</head>
<body>
    <a class="visually-hidden focus-visible" href="#main">Skip to content</a>
    <header class="site-header">
        <a class="site-brand" href="/"><?php echo h($siteTitle ?? 'Cabinet'); ?></a>
        <?php echo $this->cell('Menu::display', ['main']); ?>
    </header>
    <div class="site-header-rule"></div>
    <main id="main">
        <?php echo $this->fetch('content'); ?>
    </main>
    <footer class="site-footer">
        <div class="site-footer__inner">
            <a class="site-brand site-footer__brand" href="/"><?php echo h($siteTitle ?? 'Cabinet'); ?></a>
            <p class="site-footer__tagline"><?php echo h($tagline ?? 'Made by hand, since 1992.'); ?></p>
            <small class="site-footer__copy">© <?php echo date('Y'); ?> <?php echo h($siteTitle ?? 'Cabinet'); ?></small>
        </div>
    </footer>
    <script type="module" defer src="<?php echo $this->Url->assetUrl('/js/flash.mjs'); ?>"></script>
</body>
</html>
