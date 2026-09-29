<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\PersonalAccessToken> $tokens
 * @var list<App\Model\Enum\TokenScope> $scopes
 * @var string|null $plaintext
 * @var string $workspacePath
 */
$this->assign('title', 'API tokens');
$scopeNames = static fn (array $list): string => implode(', ', array_map(static fn ($scope): string => ucfirst($scope->value), $list));
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'API tokens', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">API tokens</h1>
        <div class="cms-page-header__meta">Personal access tokens authenticate the GraphQL API and preview builds.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php if ($plaintext !== null) { ?>
    <div class="cms-block-form cms-panel">
        <label class="cms-field">New token — copy it now, it will not be shown again
            <input type="text" value="<?php echo h($plaintext); ?>" readonly>
        </label>
    </div>
<?php } ?>

<?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/tokens/create', 'class' => 'cms-block-form cms-panel']); ?>

    <label class="cms-field">Name
        <input type="text" name="name" maxlength="120" placeholder="e.g. Preview build" required>
    </label>

    <fieldset class="cms-field">
        <legend>Scopes</legend>
        <?php foreach ($scopes as $scope) { ?>
            <label>
                <input type="checkbox" name="scopes[]" value="<?php echo h($scope->value); ?>" <?php echo $scope->value === 'read' ? 'checked' : ''; ?>>
                <?php echo h(ucfirst($scope->value)); ?>
            </label>
        <?php } ?>
        <p class="cms-field__hint">Read sees published content. Preview also sees drafts and scheduled content. Write is reserved for upcoming mutations.</p>
    </fieldset>

    <label class="cms-field">Expires (optional)
        <input type="datetime-local" name="expires_at">
    </label>

    <div class="cms-block-form__actions">
        <button type="submit" class="cms-btn cms-btn--primary">Create token</button>
    </div>
<?php echo $this->Form->end(); ?>

<div class="cms-table" role="table" aria-label="API tokens">
    <div class="cms-table__head" role="row">
        <div class="cms-table__th" role="columnheader"></div>
        <div class="cms-table__th" role="columnheader">Name</div>
        <div class="cms-table__th" role="columnheader">Scopes</div>
        <div class="cms-table__th" role="columnheader">Last used</div>
        <div class="cms-table__th" role="columnheader">Expires</div>
        <div class="cms-table__th" role="columnheader"></div>
    </div>
    <?php $hasRows = false; ?>
    <?php foreach ($tokens as $token) { ?>
        <?php $hasRows = true; ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td"></div>
            <div class="cms-table__td cms-table__td--title">
                <?php echo h($token->name); ?>
                <div class="cms-table__td--muted">by <?php echo h($token->user->name ?? '—'); ?></div>
            </div>
            <div class="cms-table__td"><?php echo h($scopeNames($token->scopeList)); ?></div>
            <div class="cms-table__td cms-table__td--muted">
                <?php echo $token->last_used_at !== null ? h($token->last_used_at->timeAgoInWords()) : 'Never'; ?>
            </div>
            <div class="cms-table__td cms-table__td--muted">
                <?php echo $token->expires_at !== null ? h($token->expires_at->format('j M Y')) : 'Never'; ?>
            </div>
            <div class="cms-table__td">
                <?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/tokens/revoke/' . (int) $token->id]); ?>
                    <button type="submit" class="cms-btn cms-btn--ghost">Revoke</button>
                <?php echo $this->Form->end(); ?>
            </div>
        </div>
    <?php } ?>
    <?php if (!$hasRows) { ?>
        <div class="cms-table__row" role="row">
            <div class="cms-table__td cms-table__td--muted cms-table__empty">No tokens yet. Create one above.</div>
        </div>
    <?php } ?>
</div>
