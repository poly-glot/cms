<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class AccountControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Settings'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $users = $this->fetchTable('Users');
        $user = $users->get(1);
        $user->password = 'current-pass-1';
        $users->saveOrFail($user);
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    private function password(int $id): string
    {
        return (string) $this->fetchTable('Users')->get($id)->password;
    }

    public function testShowsTheSignedInUsersDetails(): void
    {
        $this->get('/cabinet/admin/account');

        $this->assertResponseOk();
        $this->assertResponseContains('admin@cabinet.local');
        $this->assertResponseContains('Change password');
    }

    public function testUpdatesProfile(): void
    {
        $this->post('/cabinet/admin/account', ['name' => 'Admin Renamed', 'email' => 'admin@cabinet.local']);

        $this->assertRedirect('/cabinet/admin/account');
        $this->assertSame('Admin Renamed', $this->fetchTable('Users')->get(1)->name);
    }

    public function testRejectsAnInvalidEmail(): void
    {
        $this->post('/cabinet/admin/account', ['name' => 'Admin', 'email' => 'not-an-email']);

        $this->assertResponseOk();
        $this->assertSame('admin@cabinet.local', $this->fetchTable('Users')->get(1)->email);
    }

    public function testAllowsANameChangeWithoutTheCurrentPassword(): void
    {
        $this->post('/cabinet/admin/account', ['name' => 'Renamed Only', 'email' => 'admin@cabinet.local']);

        $this->assertRedirect('/cabinet/admin/account');
        $this->assertSame('Renamed Only', $this->fetchTable('Users')->get(1)->name);
    }

    public function testRejectsAnEmailChangeWithoutTheCurrentPassword(): void
    {
        $this->post('/cabinet/admin/account', ['name' => 'Admin', 'email' => 'moved@cabinet.local']);

        $this->assertResponseOk();
        $this->assertResponseContains('Enter your current password');
        $this->assertSame('admin@cabinet.local', $this->fetchTable('Users')->get(1)->email);
    }

    public function testChangesEmailWithTheCorrectCurrentPassword(): void
    {
        $this->post('/cabinet/admin/account', [
            'name' => 'Admin',
            'email' => 'moved@cabinet.local',
            'current_password' => 'current-pass-1',
        ]);

        $this->assertRedirect('/cabinet/admin/account');
        $this->assertSame('moved@cabinet.local', $this->fetchTable('Users')->get(1)->email);
    }

    public function testChangesPasswordWithCorrectCurrent(): void
    {
        $this->post('/cabinet/admin/account/password', [
            'current_password' => 'current-pass-1',
            'new_password' => 'brand-new-pass-2',
            'new_password_confirm' => 'brand-new-pass-2',
        ]);

        $this->assertRedirect('/cabinet/admin/account');
        $this->assertTrue(new DefaultPasswordHasher()->check('brand-new-pass-2', $this->password(1)));
    }

    public function testRejectsAnIncorrectCurrentPassword(): void
    {
        $this->post('/cabinet/admin/account/password', [
            'current_password' => 'wrong-pass',
            'new_password' => 'brand-new-pass-2',
            'new_password_confirm' => 'brand-new-pass-2',
        ]);

        $this->assertRedirect('/cabinet/admin/account');
        $this->assertTrue(new DefaultPasswordHasher()->check('current-pass-1', $this->password(1)));
    }

    public function testRejectsMismatchedNewPasswords(): void
    {
        $this->post('/cabinet/admin/account/password', [
            'current_password' => 'current-pass-1',
            'new_password' => 'brand-new-pass-2',
            'new_password_confirm' => 'different-pass-3',
        ]);

        $this->assertRedirect('/cabinet/admin/account');
        $this->assertTrue(new DefaultPasswordHasher()->check('current-pass-1', $this->password(1)));
    }

    public function testANonAdminMemberCanManageTheirOwnAccount(): void
    {
        $this->session(['Auth' => ['id' => 2, 'email' => 'eleanor@cabinet.local', 'name' => 'Eleanor Voss', 'role' => 'editor']]);
        $this->get('/cabinet/admin/account');

        $this->assertResponseOk();
        $this->assertResponseContains('eleanor@cabinet.local');
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/account');

        $this->assertRedirectContains('/login');
    }
}
