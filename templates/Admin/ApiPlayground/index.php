<?php
/**
 * @var App\View\AppView $this
 * @var string $workspacePath
 */
$this->assign('title', 'GraphQL playground');
$vendor = '/js/admin/vendor/graphiql';
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'GraphQL playground', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">GraphQL playground</h1>
        <div class="cms-page-header__meta">Autocomplete, schema docs, and runnable examples for this workspace's API.</div>
    </div>
    <div class="cms-page-header__actions">
        <a class="cms-btn" href="<?php echo h($workspacePath); ?>/admin/tokens">API tokens</a>
    </div>
</header>

<link rel="stylesheet" href="<?php echo $this->Url->assetUrl($vendor . '/graphiql.min.css'); ?>">

<div class="cms-gql"
    data-graphiql
    data-endpoint="<?php echo h($workspacePath); ?>/graphql"
    data-token-endpoint="<?php echo h($workspacePath); ?>/admin/tokens/temporary"></div>

<?php $this->append('script'); ?>
<script src="<?php echo $this->Url->assetUrl($vendor . '/react.production.min.js'); ?>"></script>
<script src="<?php echo $this->Url->assetUrl($vendor . '/react-dom.production.min.js'); ?>"></script>
<script src="<?php echo $this->Url->assetUrl($vendor . '/graphiql.min.js'); ?>"></script>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/graphql-playground.mjs'); ?>"></script>
<?php $this->end(); ?>
