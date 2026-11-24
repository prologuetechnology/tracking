<?php

use App\Models\Company;
use App\Models\DesignProof;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
    $this->style = ['colors' => ['brand' => '#195682', 'accent' => '#CEEEDD', 'paper' => '#FAFAFA'],
        'overrides' => ['foreground' => '#001122'], 'headline' => 'serif', 'corners' => 'round', 'banner' => 'canyon'];
});

it('requires staff login and keeps lookup-only staff out of design actions', function () {
    $this->get(route('admin.portal-review.index'))->assertRedirect(route('login'));
    $this->actingAs($this->makeStandardUser());
    $this->get(route('admin.portal-review.index'))->assertInertia(fn (Assert $page) => $page->component('admin/portalReview/Workbench')->where('shipment.carrierPro', 'PRO-DEMO-7018'));
    $this->get(route('admin.portal-review.classic'))->assertInertia(fn (Assert $page) => $page->component('brandedTracking/Classic')->where('initialCompany.name', 'Jayco'));
    $this->get(route('admin.portal-review.preview', $this->style))->assertForbidden();
    $this->postJson(route('admin.portal-review.proofs'), $this->style)->assertForbidden();
});

it('renders only valid fixture styles and carries token and radius overrides into the production layout', function () {
    $this->actingAs($this->makeCompanyAdmin());
    $this->get(route('admin.portal-review.preview', $this->style))->assertInertia(fn (Assert $page) => $page
        ->component('brandedTracking/Journey')->where('reviewRadius', '1rem')->where('headlineStyle', 'serif')
        ->where('initialCompany.theme.colors.root.foreground', '210 100% 6.67%')->where('initialCompany.banner.file_path', 'canyon.webp'));
    $this->postJson(route('admin.portal-review.proofs'), [...$this->style, 'overrides' => ['unknown' => '#FFFFFF'], 'banner' => '../x'])->assertUnprocessable()->assertJsonValidationErrors(['overrides', 'banner']);
    $this->get(route('admin.portal-review.asset', 'absent'))->assertNotFound();
    expect([Company::count(), DesignProof::count(), Theme::count()])->toBe([0, 0, 0]);
});
