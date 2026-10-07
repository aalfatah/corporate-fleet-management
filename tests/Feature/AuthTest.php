<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_with_remember_sets_token_and_cookie(): void
    {
        $user = User::where('email', 'ahmat.alfatah@homecc.com')->first();
        $this->assertNotNull($user);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'Admin@1234!',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->remember_token);

        // Verify remember_web cookie is present and set for persistent future expiration
        $rememberCookie = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie);
        $this->assertGreaterThan(time() + (365 * 24 * 3600), $rememberCookie->getExpiresTime());
    }

    public function test_login_without_remember_does_not_set_remember_cookie(): void
    {
        $user = User::where('email', 'ahmat.alfatah@homecc.com')->first();
        $this->assertNotNull($user);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'Admin@1234!',
            'remember' => false,
        ]);

        $response->assertRedirect(route('dashboard'));

        $rememberCookie = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertNull($rememberCookie);
    }
}
