<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Setting;
use App\Model\Tenancy\TenantContext;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Key-value site settings. The merged value map is memoised per request, so the
 * public chrome / comment flow can read it freely without re-querying — and a
 * save takes effect on the next request with no cache to invalidate.
 *
 * @extends Table<array{}, Setting>
 */
final class SettingsTable extends Table
{
    /** @var array<int, array<string, string>> */
    private array $resolved = [];

    /** @var array<string, string> Known settings and their out-of-the-box values. */
    public const array DEFAULTS = [
        'site_title' => 'Cabinet',
        'tagline' => 'Made by hand, since 1992.',
        'theme' => 'heritage',
        'allow_comments' => '1',
        'moderation_queue' => '1',
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('settings');
        $this->addBehavior('Tenant');
        $this->setPrimaryKey('id');
        $this->setDisplayField('setting_key');
        $this->setEntityClass(Setting::class);
        $this->addBehavior('Timestamp');
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('setting_key', 'create')
            ->inList('setting_key', array_keys(self::DEFAULTS));
        $validator->maxLength('value', 65535)->allowEmptyString('value');

        return $validator;
    }

    /**
     * The full settings map: defaults overlaid with stored values.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $workspaceId = TenantContext::instance()->workspaceId;
        if ($workspaceId === null) {
            return self::DEFAULTS;
        }

        return $this->resolved[$workspaceId] ??= $this->buildMap();
    }

    /**
     * @return array<string, string>
     */
    private function buildMap(): array
    {
        $stored = [];
        foreach ($this->find()->all() as $setting) {
            $stored[$setting->setting_key] = (string) $setting->value;
        }

        return array_merge(self::DEFAULTS, $stored);
    }

    public function value(string $key): string
    {
        return $this->all()[$key] ?? '';
    }

    public function isEnabled(string $key): bool
    {
        return $this->value($key) === '1';
    }

    /**
     * Upserts each known setting; unknown keys are ignored. Resets the per-request
     * memo so a subsequent read in the same request sees the new values.
     *
     * @param array<string, string> $values
     */
    public function writeMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            $setting = $this->find()->where(['setting_key' => $key])->first()
                ?? $this->newEmptyEntity();
            $this->patchEntity($setting, ['setting_key' => $key, 'value' => $value]);
            $this->saveOrFail($setting);
        }

        $this->resolved = [];
    }
}
