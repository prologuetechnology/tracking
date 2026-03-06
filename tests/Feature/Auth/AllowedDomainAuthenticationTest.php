<?php

namespace Tests\Feature\Auth;

use App\Models\AllowedDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AllowedDomainAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_callback_rejects_users_from_inactive_domains(): void
    {
        $admin = User::factory()->create();

        AllowedDomain::query()->create([
            'domain' => 'example.com',
            'description' => 'Inactive test domain',
            'is_active' => false,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Socialite::shouldReceive('driver')->once()->with('azure')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn(
            $this->makeSocialiteUser('azure-1', 'person@example.com'),
        );

        $this->get(route('oauth.callback', ['provider' => 'azure']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_oauth_callback_creates_a_standard_user_for_active_domains(): void
    {
        $admin = User::factory()->create();

        AllowedDomain::query()->create([
            'domain' => 'example.com',
            'description' => 'Active test domain',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Socialite::shouldReceive('driver')->once()->with('azure')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn(
            $this->makeSocialiteUser('azure-2', 'person@example.com'),
        );

        $this->get(route('oauth.callback', ['provider' => 'azure']))
            ->assertRedirect(route('home'));

        $user = User::query()->where('email', 'person@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('Standard'));
    }

    private function makeSocialiteUser(string $azureId, string $email): SocialiteUser
    {
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = $azureId;
        $socialiteUser->email = $email;
        $socialiteUser->token = 'token';
        $socialiteUser->refreshToken = 'refresh-token';
        $socialiteUser->avatar = 'https://example.com/avatar.png';
        $socialiteUser->user = [
            'givenName' => 'Taylor',
            'surname' => 'Otwell',
        ];

        return $socialiteUser;
    }
}
