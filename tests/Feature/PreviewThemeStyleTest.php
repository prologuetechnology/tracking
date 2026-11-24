<?php

use App\Actions\DesignDrafts\DraftPalette;
use App\Models\ThemeVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('previews derived and overridden tokens without writing any library or customer records', function () {
    $this->seedCoreFixtures();
    $company = $this->makeCompany();
    $versionCount = ThemeVersion::query()->count();
    $style = [
        'colors' => ['brand' => '#16406A', 'accent' => '#EDB458', 'paper' => '#FFFFFF'],
        'headline' => 'serif', 'corners' => 'round', 'overrides' => ['ring' => '#FF00FF'],
    ];
    $this->postJson(route('api.themes.preview'), ['style' => $style])->assertUnauthorized();
    $this->actingAs($this->makeStandardUser())->postJson(route('api.themes.preview'), ['style' => $style])->assertForbidden();

    $staff = $this->makeUserWithPermission('theme:update');
    $this->actingAs($staff)->postJson(route('api.themes.preview'), ['style' => $style])->assertOk()
        ->assertJsonPath('derived.ring', DraftPalette::theme($style['colors'])['colors']['root']['ring'])
        ->assertJsonPath('appearance.colors.root.ring', DraftPalette::colorToken('#FF00FF'))
        ->assertJsonPath('appearance.radius', '1rem');
    $this->postJson(route('api.themes.preview'), ['style' => [...$style, 'colors' => ['brand' => 'invalid']]])
        ->assertUnprocessable()->assertJsonValidationErrors(['style.colors.brand', 'style.colors.accent', 'style.colors.paper']);
    $this->assertDatabaseCount('theme_versions', $versionCount);
    $this->assertDatabaseCount('design_draft_revisions', 0);
    $this->assertSame($company->theme_id, $company->fresh()->theme_id);
});
