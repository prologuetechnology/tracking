<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ThemeAndAllowedDomainSurfaceCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_pages_and_api_cover_crud_and_permissions(): void
    {
        $this->seedCoreFixtures();

        $theme = $this->makeTheme(['name' => 'Existing Theme']);
        $viewer = $this->makeUserWithPermission('theme:show');
        $creator = $this->makeUserWithPermission('theme:store');
        $updater = $this->makeUserWithPermission('theme:update');
        $destroyer = $this->makeUserWithPermission('theme:destroy');

        $this->actingAs($viewer)
            ->get(route('admin.studio.styles.show', $theme->uuid))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/designDrafts/StyleLibrary')->where('mode', 'inspect')
                ->where('initialTheme.name', 'Existing Theme'));

        $payload = [
            'name' => 'Brand New Theme',
            'primary_hue' => [210],
            'primary_saturation' => [70],
            'primary_lightness' => [40],
            'accent_hue' => [18],
            'accent_saturation' => [80],
            'accent_lightness' => [55],
            'derive_from' => 'primary',
            'radius' => '0.75rem',
            'is_system' => false,
        ];

        $created = $this->actingAs($creator)
            ->postJson(route('api.themes.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Brand New Theme')
            ->json();

        $this->actingAs($updater)
            ->putJson(route('api.themes.update', $theme), [
                ...$payload,
                'name' => 'Updated Theme',
                'base_version' => 1,
                'derive_from' => 'accent',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Theme');

        $this->actingAs($destroyer)
            ->deleteJson(route('api.themes.destroy', $created['id']))
            ->assertNoContent();

        $this->actingAs($this->makeStandardUser(['email' => 'themes-denied@test.local']))
            ->postJson(route('api.themes.store'), $payload)
            ->assertForbidden();
    }

    public function test_application_settings_protects_its_access_pages_and_preserves_existing_bookmarks(): void
    {
        $this->seedCoreFixtures();
        $routes = ['users' => 'users.index', 'role' => 'role.index', 'permissions' => 'permissions.index', 'allowed-domains' => 'allowed-domains.index'];
        $this->actingAs($this->makeStandardUser())->get(route('admin.settings.index'))->assertForbidden();
        foreach ($routes as $route) {
            $this->get(route('admin.'.$route))->assertForbidden();
        }
        $this->actingAs($this->makeSuperAdmin())->get(route('admin.settings.index'))->assertInertia(fn (Assert $page) => $page->component('admin/settings/Index'));
        foreach ($routes as $legacy => $route) {
            $this->get('/admin/'.$legacy)->assertRedirectToRoute('admin.'.$route);
            $this->get(route('admin.'.$route))->assertOk();
        }
        $this->get(route('admin.companies.index', ['workspace' => 'studio']))->assertRedirectToRoute('admin.companies.index');
    }

    public function test_allowed_domain_pages_and_api_cover_crud_toggle_and_super_admin_boundaries(): void
    {
        $this->seedCoreFixtures();

        $superAdmin = $this->makeSuperAdmin();
        $domain = $this->makeAllowedDomain($superAdmin, ['domain' => 'example.com', 'is_active' => true]);

        $this->actingAs($superAdmin)
            ->get(route('admin.allowed-domains.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/allowedDomains/Index')
                ->has('initialAllowedDomains', 1));

        $created = $this->actingAs($superAdmin)
            ->postJson(route('api.admin.allowedDomains.store'), [
                'domain' => 'created.com',
                'description' => 'Created domain',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('domain', 'created.com')
            ->json();

        $this->actingAs($superAdmin)
            ->putJson(route('api.admin.allowedDomains.update', $domain), [
                'domain' => 'updated.com',
                'description' => 'Updated domain',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('domain', 'updated.com');

        $this->actingAs($superAdmin)
            ->patchJson(route('api.admin.allowedDomains.toggleActiveStatus', $domain))
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->actingAs($superAdmin)
            ->deleteJson(route('api.admin.allowedDomains.destroy', $created['id']))
            ->assertNoContent();

        $nonSuperAdmin = $this->makeStandardUser(['email' => 'domains-denied@test.local']);
        $this->actingAs($nonSuperAdmin)->get(route('admin.allowed-domains.index'))->assertForbidden();
    }
}
