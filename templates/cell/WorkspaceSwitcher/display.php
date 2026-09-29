<?php
/**
 * @var App\View\AppView $this
 * @var array<int, App\Model\Entity\Membership> $memberships
 * @var int|null $activeWorkspaceId
 */
if (count($memberships) === 0) {
    return;
}

$active = null;
$others = [];
foreach ($memberships as $membership) {
    if ($membership->workspace_id === $activeWorkspaceId) {
        $active = $membership;
    } else {
        $others[] = $membership;
    }
}
$summaryLabel = $active !== null ? $active->workspace->name : 'Choose workspace';
?>
<details class="cms-workspace-switcher">
    <summary class="cms-workspace-switcher__trigger" aria-label="Workspace menu">
        <span class="cms-workspace-switcher__label"><?php echo h($summaryLabel); ?></span>
        <span class="cms-workspace-switcher__chevron" aria-hidden="true">&#9662;</span>
    </summary>
    <div class="cms-workspace-switcher__menu" role="menu">
        <?php if ($active !== null) { ?>
            <div class="cms-workspace-switcher__group">
                <p class="cms-workspace-switcher__heading">Current workspace</p>
                <span class="cms-workspace-switcher__item cms-workspace-switcher__item--current" aria-current="true">
                    <span class="cms-workspace-switcher__item-name"><?php echo h($active->workspace->name); ?></span>
                    <span class="cms-workspace-switcher__item-role"><?php echo h($active->roleEnum->label()); ?></span>
                </span>
            </div>
        <?php } ?>
        <?php if ($others !== []) { ?>
            <div class="cms-workspace-switcher__group">
                <p class="cms-workspace-switcher__heading">Switch to</p>
                <?php foreach ($others as $other) { ?>
                    <a class="cms-workspace-switcher__item" role="menuitem"
                       href="/<?php echo h($other->workspace->slug); ?>/admin">
                        <span class="cms-workspace-switcher__item-name"><?php echo h($other->workspace->name); ?></span>
                        <span class="cms-workspace-switcher__item-role"><?php echo h($other->roleEnum->label()); ?></span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
        <div class="cms-workspace-switcher__group">
            <a class="cms-workspace-switcher__item cms-workspace-switcher__item--new"
               role="menuitem" href="/workspaces/new">
                <span aria-hidden="true">+</span>
                <span>Create new workspace</span>
            </a>
        </div>
    </div>
</details>
