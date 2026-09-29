<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $filename
 * @property string $mime
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt
 * @property int $uploaded_by
 * @property DateTime $created
 * @property DateTime $modified
 * @property array<MediaRendition> $renditions
 */
final class Media extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'alt' => true,
    ];

    public bool $isImage {
        get => str_starts_with((string) $this->mime, 'image/');
    }

    public string $kind {
        get => match (true) {
            str_starts_with((string) $this->mime, 'image/') => 'image',
            str_starts_with((string) $this->mime, 'audio/') => 'audio',
            str_starts_with((string) $this->mime, 'video/') => 'video',
            default => 'document',
        };
    }

    public string $extension {
        get => strtolower(pathinfo((string) $this->name, \PATHINFO_EXTENSION));
    }
}
