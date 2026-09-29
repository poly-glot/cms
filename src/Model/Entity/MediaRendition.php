<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $media_id
 * @property string $name
 * @property string $filename
 * @property int|null $width
 * @property int|null $height
 * @property int $size
 * @property DateTime $created
 * @property Media|null $media
 */
final class MediaRendition extends Entity
{
    protected array $_accessible = [
        'media_id' => true,
        'name' => true,
        'filename' => true,
        'width' => true,
        'height' => true,
        'size' => true,
    ];
}
