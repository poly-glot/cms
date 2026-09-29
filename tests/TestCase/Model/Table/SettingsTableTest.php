<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SettingsTable;
use App\Model\Tenancy\TenantContext;
use Cake\TestSuite\TestCase;

final class SettingsTableTest extends TestCase
{
    protected array $fixtures = ['app.Settings'];

    public function testReturnsDefaultsWhenEmpty(): void
    {
        $settings = $this->fetchTable('Settings');

        $this->assertSame('Cabinet', $settings->value('site_title'));
        $this->assertTrue($settings->isEnabled('moderation_queue'));
        $this->assertSame('', $settings->value('unknown_key'));
    }

    public function testReturnsDefaultsWhenNoActiveWorkspace(): void
    {
        TenantContext::instance()->clear();

        $this->assertSame(SettingsTable::DEFAULTS, $this->fetchTable('Settings')->all());
        $this->assertSame('Cabinet', $this->fetchTable('Settings')->value('site_title'));
    }

    public function testWriteManyOverridesDefaultsAndKeepsOthers(): void
    {
        $settings = $this->fetchTable('Settings');

        $settings->writeMany(['site_title' => 'New & Co', 'moderation_queue' => '0']);

        $this->assertSame('New & Co', $settings->value('site_title'));
        $this->assertFalse($settings->isEnabled('moderation_queue'));
        $this->assertSame('Made by hand, since 1992.', $settings->value('tagline'));
    }

    public function testWriteManyIgnoresUnknownKeys(): void
    {
        $settings = $this->fetchTable('Settings');

        $settings->writeMany(['bogus' => 'x']);

        $this->assertFalse($settings->exists(['setting_key' => 'bogus']));
    }
}
