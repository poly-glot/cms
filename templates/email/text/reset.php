<?php

declare(strict_types=1);
/**
 * @var App\View\AppView $this
 * @var App\Model\Entity\User $user
 * @var string $link
 * @var string $siteName
 */
echo "Hi {$user->name},\n\n";
echo "We received a request to reset your {$siteName} password.\n\n";
echo "Choose a new password using the link below:\n";
echo "{$link}\n\n";
echo "This link expires in one hour. If you didn't request this, you can ignore it.\n\n";
echo "— {$siteName}\n";
