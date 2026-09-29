<?php
/**
 * @var App\View\AppView $this
 * @var iterable<App\Model\Entity\Membership> $members
 * @var list<App\Model\Enum\UserRole> $roles
 * @var string $workspacePath
 */
$this->assign('title', 'Users & Roles');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => 'Users', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title">Users &amp; Roles</h1>
        <div class="cms-page-header__meta">Everyone with access to this workspace.</div>
    </div>
    <div class="cms-page-header__actions">
        <a class="cms-btn cms-btn--primary" href="<?php echo h($workspacePath); ?>/admin/users/invite">+ Invite</a>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<div class="cms-roster">
    <?php foreach ($members as $member) { ?>
        <article class="cms-member">
            <div class="cms-member__avatar" style="<?php echo h($member->user->avatarStyle); ?>" aria-hidden="true"></div>

            <div class="cms-member__info">
                <span class="cms-member__name"><?php echo h($member->user->name); ?></span>
                <span class="cms-member__email"><?php echo h($member->user->email); ?></span>
                <span class="cms-member__meta"><?php echo h($member->roleEnum->description()); ?> &middot; joined <?php echo h($member->created->format('Y')); ?></span>
            </div>

            <?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/users/edit-role/' . (int) $member->id, 'class' => 'cms-member__role']); ?>
                <div class="cms-select">
                    <select class="cms-select__control" name="role" data-autosubmit aria-label="Role for <?php echo h($member->user->name); ?>">
                        <?php foreach ($roles as $role) { ?>
                            <option value="<?php echo h($role->value); ?>" <?php echo $member->role === $role->value ? 'selected' : ''; ?>>
                                <?php echo h($role->label()); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            <?php echo $this->Form->end(); ?>

            <?php echo $this->Form->create(null, ['url' => $workspacePath . '/admin/users/send-reset/' . (int) $member->id, 'class' => 'cms-member__actions']); ?>
                <button type="submit" class="cms-btn cms-btn--ghost">Send reset link</button>
            <?php echo $this->Form->end(); ?>
        </article>
    <?php } ?>
</div>
