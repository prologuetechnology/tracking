<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageHydrationCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures();
    }

    public function test_home_and_login_routes_respect_auth_state(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));

        $admin = $this->makeUserWithPermission('company:show');

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertRedirect(route('admin.companies.index'));
    }

    public function test_dashboard_redirects_to_the_correct_surface_for_each_user_type(): void
    {
        $companyViewer = $this->makeUserWithPermission('company:show');
        $trackingOnly = $this->makeStandardUser();

        $this->actingAs($companyViewer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.companies.index'));

        $this->actingAs($trackingOnly)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.tracking.index'));
    }

    public function test_create_and_tracking_pages_render_through_inertia_page_controllers(): void
    {
        $companyCreator = $this->makeUserWithPermission('company:store');
        $themeCreator = $this->makeUserWithPermission('theme:store');
        $superAdmin = $this->makeSuperAdmin();

        $this->actingAs($companyCreator)
            ->get(route('admin.companies.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/companies/Create'));

        $this->actingAs($themeCreator)
            ->get(route('admin.themes.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/themes/Create'));

        $this->actingAs($superAdmin)
            ->get(route('admin.permissions.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/permissions/Create'));

        $this->actingAs($this->makeStandardUser(['email' => 'tracking@test.local']))
            ->get(route('admin.tracking.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/tracking/Index'));
    }
}
