<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Enum\TokenScope;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class TokensControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.PersonalAccessTokens'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    public function testAdminCreatesTokenStoringHashAndScopes(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local']]);

        $this->post('/cabinet/admin/tokens/create', ['name' => 'Build token', 'scopes' => ['read', 'preview']]);
        $this->assertRedirect('/cabinet/admin/tokens');

        $token = $this->fetchTable('PersonalAccessTokens')
            ->find()->where(['name' => 'Build token'])->firstOrFail();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->token_hash);
        $this->assertEqualsCanonicalizing([TokenScope::Read, TokenScope::Preview], $token->scopeList);
    }

    public function testIndexRendersTheOneTimePlaintextFromSession(): void
    {
        $plaintext = str_repeat('a', 64);
        $this->session([
            'Auth' => ['id' => 1, 'email' => 'admin@cabinet.local'],
            'Tokens.plaintext' => $plaintext,
        ]);

        $this->get('/cabinet/admin/tokens');

        $this->assertResponseOk();
        $this->assertResponseContains($plaintext);
    }

    public function testNonAdminCannotCreateToken(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local']]);

        $this->post('/cabinet/admin/tokens/create', ['name' => 'Sneaky', 'scopes' => ['write']]);

        $this->assertResponseCode(403);
        $this->assertSame(0, $this->fetchTable('PersonalAccessTokens')
            ->find()->where(['name' => 'Sneaky'])->count());
    }

    public function testAdminRevokesToken(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local']]);

        $this->post('/cabinet/admin/tokens/revoke/1');

        $this->assertRedirect('/cabinet/admin/tokens');
        $this->assertFalse($this->fetchTable('PersonalAccessTokens')->exists(['id' => 1]));
    }

    public function testTemporaryMintsAShortLivedWriteTokenAsJson(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local']]);

        $this->post('/cabinet/admin/tokens/temporary');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('"token"');

        $token = $this->fetchTable('PersonalAccessTokens')
            ->find()->where(['name' => 'Playground (temporary)'])->firstOrFail();
        $this->assertNotNull($token->expires_at);
        $this->assertContains(TokenScope::Write, $token->scopeList);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->token_hash);
    }
}
