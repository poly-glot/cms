<?php

declare(strict_types=1);
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\User $user
 * @var App\Model\Enum\UserRole $role
 * @var string $link
 * @var string $siteName
 */
echo "Hi {$user->name},\n\n";
echo "You've been invited to {$siteName} as a {$role->label()}.\n\n";
echo "Set your password to finish setting up your account:\n";
echo "{$link}\n\n";
echo "This link expires in seven days. If you weren't expecting this, you can ignore it.\n\n";
echo "— {$siteName}\n";
