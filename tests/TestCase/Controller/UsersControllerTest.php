<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\User;
use App\Service\User\InvitationService;
use Cake\Http\Cookie\CookieCollection;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class UsersControllerTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $users = $this->fetchTable('Users');
        $user = $users->get(1);
        $user->password = 'secret-pass-1';
        $users->saveOrFail($user);
    }

    public function testGetLoginRendersForm(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('Sign in');
    }

    public function testLoginLinksToForgotPassword(): void
    {
        $this->get('/login');

        $this->assertResponseContains('href="/forgot-password"');
    }

    public function testPostLoginGoodCredentialsRedirectsToAdmin(): void
    {
        $this->post('/login', [
            'email' => 'admin@cabinet.local',
            'password' => 'secret-pass-1',
        ]);

        $this->assertRedirect('/');
    }

    public function testPostLoginBadCredentialsShowsError(): void
    {
        $this->post('/login', [
            'email' => 'admin@cabinet.local',
            'password' => 'wrong-password',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Email or password is incorrect.');
    }

    public function testLoginSetsNoCookieThatFirebaseHostingWouldStrip(): void
    {
        $this->post('/login', [
            'email' => 'admin@cabinet.local',
            'password' => 'secret-pass-1',
            'remember_me' => '1',
        ]);

        $this->assertRedirect('/');
        $this->assertFalse($this->responseCookies()->has('CabinetRemember'));
        $this->assertFalse($this->responseCookies()->has('csrfToken'));
    }

    public function testPostLogoutRedirectsToLogin(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local']]);
        $this->post('/logout');

        $this->assertRedirect('/login');
    }

    public function testGetSetPasswordRendersForm(): void
    {
        ['token' => $token] = $this->invite();

        $this->get('/users/set-password/' . $token);

        $this->assertResponseOk();
        $this->assertResponseContains('Set your password');
        $this->assertResponseContains('name="password"');
    }

    public function testPostSetPasswordPersistsAndRedirectsToLogin(): void
    {
        ['token' => $token] = $this->invite();

        $this->post('/users/set-password/' . $token, ['password' => 'super-secret-1']);

        $this->assertRedirect('/login');
        $user = $this->fetchTable('Users')
            ->find()->where(['email' => 'pat@example.com'])->firstOrFail();
        $this->assertNotNull($user->password);
        $this->assertNull($user->invitation_token_hash);
        $this->assertNotNull($user->accepted_at);
    }

    public function testPostSetPasswordShowsErrorOnUnknownToken(): void
    {
        $this->post('/users/set-password/' . str_repeat('a', 64), ['password' => 'super-secret-1']);

        $this->assertResponseOk();
        $this->assertResponseContains('no longer valid');
    }

    public function testSetPasswordRouteRejectsBadTokenShape(): void
    {
        $this->get('/users/set-password/not-a-token');

        $this->assertResponseCode(404);
    }

    private function responseCookies(): CookieCollection
    {
        $this->assertNotNull($this->_response);

        return CookieCollection::createFromHeader($this->_response->getHeader('Set-Cookie'));
    }

    public function testGetForgotPasswordRendersForm(): void
    {
        $this->get('/forgot-password');

        $this->assertResponseOk();
        $this->assertResponseContains('Forgot your password');
        $this->assertResponseContains('name="email"');
    }

    public function testPostForgotPasswordKnownEmailRedirectsWithNeutralFlash(): void
    {
        $this->post('/forgot-password', ['email' => 'admin@cabinet.local']);

        $this->assertRedirect('/login');
        $this->assertFlashMessage('If an account exists for that email, a reset link is on its way.');
    }

    public function testPostForgotPasswordUnknownEmailRedirectsWithSameFlash(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertRedirect('/login');
        $this->assertFlashMessage('If an account exists for that email, a reset link is on its way.');
    }

    public function testGetResetPasswordRendersForm(): void
    {
        $token = $this->issueResetToken();

        $this->get('/reset-password/' . $token);

        $this->assertResponseOk();
        $this->assertResponseContains('Reset your password');
        $this->assertResponseContains('name="password"');
    }

    public function testPostResetPasswordValidTokenSetsPasswordAndRedirects(): void
    {
        $token = $this->issueResetToken();

        $this->post('/reset-password/' . $token, ['password' => 'brand-new-pass-1']);

        $this->assertRedirect('/login');
        $user = $this->fetchTable('Users')->get(1);
        $this->assertNull($user->password_reset_token_hash);
    }

    public function testPostResetPasswordInvalidTokenShowsError(): void
    {
        $this->post('/reset-password/' . str_repeat('a', 64), ['password' => 'brand-new-pass-1']);

        $this->assertResponseOk();
        $this->assertResponseContains('no longer valid');
    }

    private function issueResetToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $users = $this->fetchTable('Users');
        $user = $users->get(1);
        $user->password_reset_token_hash = hash('sha256', $token);
        $user->password_reset_expires_at = DateTime::now()->addHours(1);
        $users->saveOrFail($user);

        return $token;
    }

    /** @return array{user: User, token: string} */
    private function invite(): array
    {
        $result = new InvitationService()->invite('Pat', 'pat@example.com', 'author');

        return ['user' => $result['user'], 'token' => (string) $result['token']];
    }
}
