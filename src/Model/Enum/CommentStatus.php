<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
