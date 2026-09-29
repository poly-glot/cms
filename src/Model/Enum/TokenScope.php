<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum TokenScope: string
{
    case Read = 'read';
    case Preview = 'preview';
    case Write = 'write';

    public function isAtLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Read => 1,
            self::Preview => 2,
            self::Write => 3,
        };
    }
}
