<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\TokenScope;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property string $name
 * @property string $token_hash
 * @property list<string> $scopes
 * @property DateTime|null $last_used_at
 * @property DateTime|null $expires_at
 * @property DateTime $created
 * @property DateTime $modified
 * @property User $user
 */
final class PersonalAccessToken extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'scopes' => true,
        'expires_at' => true,
    ];

    protected array $_hidden = ['token_hash'];

    /** @var list<TokenScope> */
    public array $scopeList {
        get => array_values(array_filter(array_map(TokenScope::tryFrom(...), $this->scopes)));
    }
}
