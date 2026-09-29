<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\PersonalAccessToken;
use App\Model\Enum\TokenScope;
use App\Model\Table\PersonalAccessTokensTable;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;

final class PersonalAccessTokensTableTest extends TestCase
{
    use LocatorAwareTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.PersonalAccessTokens'];

    public function testResolvesValidTokenToUserAndWorkspace(): void
    {
        $issued = $this->tokens()->issue(1, 1, 'CI token', [TokenScope::Read, TokenScope::Preview], null);

        $resolved = $this->tokens()->resolve($issued['token']);

        $this->assertInstanceOf(PersonalAccessToken::class, $resolved);
        $this->assertSame(1, $resolved->user->id);
        $this->assertSame(1, $resolved->workspace_id);
        $this->assertEqualsCanonicalizing([TokenScope::Read, TokenScope::Preview], $resolved->scopeList);
    }

    public function testIssueStoresOnlyTheHashAndHidesIt(): void
    {
        $issued = $this->tokens()->issue(1, 1, 'CI token', [TokenScope::Read], null);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $issued['token']);
        $this->assertSame(hash('sha256', $issued['token']), $issued['entity']->token_hash);
        $this->assertNotSame($issued['token'], $issued['entity']->token_hash);
        $this->assertArrayNotHasKey('token_hash', $issued['entity']->toArray());
    }

    public function testRejectsExpiredToken(): void
    {
        $issued = $this->tokens()->issue(1, 1, 'Expired token', [TokenScope::Read], new DateTime('-1 hour'));

        $this->assertNull($this->tokens()->resolve($issued['token']));
    }

    public function testRejectsUnknownToken(): void
    {
        $this->assertNull($this->tokens()->resolve('never-issued-token'));
    }

    private function tokens(): PersonalAccessTokensTable
    {
        return $this->fetchTable('PersonalAccessTokens');
    }
}
