<?php
/** @var Cake\View\View $this */
$identity = $this->getRequest()->getAttribute('identity');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?php echo $this->getRequest()->getAttribute('csrfToken'); ?>">
    <meta name="workspace-path" content="<?php echo h($workspacePath); ?>">
    <title><?php echo h($this->fetch('title')); ?> — Cabinet</title>
    <?php echo $this->Html->css([
        'tokens',
        'admin/reset', 'admin/base', 'admin/layout',
        'admin/components/controls', 'admin/components/editor', 'admin/components/widgets', 'admin/components/blocks', 'admin/components/overlays',
        'admin/surfaces/editor', 'admin/surfaces/schema', 'admin/surfaces/content', 'admin/surfaces/comments', 'admin/surfaces/nav', 'admin/surfaces/users',
    ]); ?>
</head>
<body class="cms-app">
    <?php echo $this->element('admin/topbar', ['identity' => $identity]); ?>
    <div class="cms-body">
        <?php echo $this->element('admin/sidebar'); ?>
        <main class="cms-main">
            <?php echo $this->fetch('content'); ?>
        </main>
    </div>
    <script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/main.mjs'); ?>"></script>
    <script type="module" defer src="<?php echo $this->Url->assetUrl('/js/flash.mjs'); ?>"></script>
    <?php echo $this->fetch('script'); ?>
</body>
</html>
