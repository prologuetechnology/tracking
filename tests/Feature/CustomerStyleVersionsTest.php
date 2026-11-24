<?php

use App\Actions\DesignDrafts\ChangeTrackingPublication;
use App\Actions\DesignDrafts\ClassicDesign;
use App\Actions\DesignDrafts\SaveDesignDraft;
use App\Actions\Themes\BackfillThemeVersions;
use App\Actions\Themes\ResolveThemeStyle;
use App\Http\Resources\CompanyResource;
use App\Models\Theme;
use App\Models\ThemeVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
    $this->actingAs($this->staff = $this->makeSuperAdmin());
    $this->style = [
        'colors' => ['brand' => '#145A76', 'accent' => '#C1DFCF', 'paper' => '#FBFBF5'],
        'overrides' => ['foreground' => '#112233'], 'headline' => 'serif', 'corners' => 'round',
    ];
});

it('backfills exact legacy tokens and pins once while preserving unthemed companies', function () {
    $colors = ['root' => ['background' => '37 4.1% 78.3%', 'customPreservedToken' => '182 23% 41%'], 'dark' => ['background' => '220 30% 10%']];
    $themeId = DB::table('themes')->insertGetId(['uuid' => (string) Str::uuid(), 'name' => 'Imported style',
        'derive_from' => 'accent', 'colors' => json_encode($colors), 'is_system' => 0, 'radius' => '0.617rem']);
    $legacy = $this->makeCompany(['theme_id' => null]);
    DB::table('companies')->where('id', $legacy->id)->update(['theme_id' => $themeId]);
    $unbranded = $this->makeCompany(['theme_id' => null]);
    $missing = $this->makeCompany(['theme_id' => null]);
    DB::table('companies')->where('id', $missing->id)->update(['theme_id' => 987654321]);

    app(BackfillThemeVersions::class)->execute();
    $first = Theme::findOrFail($themeId)->versions()->sole();
    $this->get(route('admin.drafts.create', $legacy->uuid))->assertInertia(fn ($page) => $page
        ->where('initialValues.layout', 'classic.v1')->where('initialValues.theme_version_id', $first->id)
        ->where('classicStyle.colors', $colors)->where('classicStyle.radius', '0.617rem'));
    $this->get(route('admin.drafts.create', $unbranded->uuid))->assertInertia(fn ($page) => $page
        ->where('classicStyle', null)->where('initialValues.theme_version_id', null));
    expect($first->colors)->toBe($colors)->and($first->radius)->toBe('0.617rem')
        ->and($legacy->fresh()->theme_version_id)->toBe($first->id)
        ->and($unbranded->fresh()->theme_version_id)->toBeNull()
        ->and($missing->fresh()->theme_version_id)->toBeNull()
        ->and(ClassicDesign::editorSettings($missing->fresh(), null)['theme_id'])->toBeNull();

    $retired = $this->makeTheme();
    $retiredCompany = $this->makeCompany(['theme_id' => $retired->id]);
    DB::table('themes')->where('id', $retired->id)->update(['deleted_at' => now()]);
    $retiredSettings = ClassicDesign::editorSettings($retiredCompany->fresh(), null);
    expect($retiredSettings['theme_id'])->toBeNull()->and($retiredSettings['theme_version_id'])->toBeNull();

    $this->putJson(route('api.themes.update', $themeId), ['name' => 'Guided style', 'base_version' => 1, 'style' => $this->style])
        ->assertOk()->assertJsonPath('version', 2);
    app(BackfillThemeVersions::class)->execute();
    $company = CompanyResource::make($legacy->fresh(['theme', 'themeVersion']))->resolve();
    expect($company['theme']['colors'])->toBe($colors)->and($company['theme']['name'])->toBe('Imported style')
        ->and($company['theme']['radius'])->toBe('0.617rem')->and($company['theme']['theme_version_id'])->toBe($first->id)
        ->and(ThemeVersion::where('theme_id', $themeId)->count())->toBe(2);
});

it('reports a mismatched initial style during migration reconciliation', function () {
    $theme = $this->makeTheme();
    $first = $theme->versions()->sole();
    DB::table('theme_versions')->where('id', $first->id)->update(['radius' => '0.999rem']);
    expect(fn () => app(BackfillThemeVersions::class)->execute())->toThrow(RuntimeException::class, "version 1 of theme {$theme->id}");
});

it('clones an exact style, validates overrides and rejects competing saves', function () {
    $theme = $this->makeTheme(['radius' => '0.375rem']);
    $first = $theme->versions()->sole();
    $url = route('api.themes.update', $theme);
    $this->putJson($url, ['name' => 'Copy', 'source_version_id' => $first->id, 'base_version' => 1])
        ->assertOk()->assertJsonPath('version', 2)->assertJsonPath('style', null)->assertJsonPath('radius', $theme->radius);
    $this->putJson($url, ['name' => 'Stale', 'style' => $this->style, 'base_version' => 1])
        ->assertUnprocessable()->assertJsonValidationErrors('base_version');
    $other = $this->makeTheme();
    $this->putJson($url, ['name' => 'Cross-theme copy', 'source_version_id' => $other->versions()->sole()->id, 'base_version' => 2])
        ->assertUnprocessable()->assertJsonValidationErrors('source_version_id');
    $this->putJson($url, ['name' => 'Invalid', 'style' => [...$this->style, 'overrides' => ['arbitrary' => '#111111']], 'base_version' => 2])
        ->assertUnprocessable()->assertJsonValidationErrors('style.overrides');
    $updated = $this->putJson($url, ['name' => 'Final', 'style' => $this->style, 'base_version' => 2])->assertOk()
        ->assertJsonPath('version', 3)->assertJsonPath('radius', '1rem')->json();
    expect(fn () => $first->update(['name' => 'Overwrite']))->toThrow(LogicException::class);
    expect($updated['colors'])->toBe(ResolveThemeStyle::attributes($this->style)['colors'])
        ->and($first->fresh()->colors)->toBe($theme->colors)->and($theme->versions()->count())->toBe(3);
});

it('keeps the Classic baseline and drafts pinned through publication, fork, and rollback', function () {
    $theme = $this->makeTheme();
    $company = $this->makeCompany(['theme_id' => $theme->id]);
    $first = $theme->versions()->sole();
    $second = $this->putJson(route('api.themes.update', $theme), ['name' => $theme->name, 'base_version' => 1, 'style' => $this->style])
        ->assertOk()->json('theme_version_id');
    $input = [...ClassicDesign::editorSettings($company, null), 'name' => 'Reviewed design', 'layout' => 'journey.v1',
        'theme_id' => $theme->id, 'theme_version_id' => $second, 'search_option' => 'bol', 'tracking_number' => 'DEMO-1'];
    $save = app(SaveDesignDraft::class);
    $draft = $save->execute($company, $input, $this->staff->id);
    $revision = $draft->revisions()->sole();
    expect($revision->theme_version_id)->toBe($second)->and($revision->design['theme']['version'])->toBe(2);
    $usage = $this->getJson(route('api.themes.show', $theme))->assertOk()->json('usage');
    expect(collect($usage)->pluck('version', 'kind')->all())->toEqual(['live' => 1, 'draft' => 2]);

    $this->putJson(route('api.themes.update', $theme), ['name' => $theme->name, 'base_version' => 2,
        'style' => [...$this->style, 'corners' => 'square']])->assertOk();
    $this->deleteJson(route('api.themes.destroy', $theme))->assertUnprocessable();
    $fork = $save->fork($company, $revision, 'Alternate', $this->staff->id);
    expect($fork->revisions()->sole()->design)->toBe($revision->design)
        ->and($company->fresh()->theme_version_id)->toBe($first->id);
    $proof = $revision->proofs()->create(['snapshot' => [], 'created_by' => $this->staff->id, 'shipment_reference' => 'DEMO-1']);
    $proof->forceFill(['status' => 'ready', 'approved_at' => now()])->save();
    $publish = app(ChangeTrackingPublication::class);
    $publish->execute($company, $proof, 0, $this->staff->id);
    $active = $company->publications()->latest('id')->first();
    expect($active->effectiveRevision()->design['theme']['version'])->toBe(2);
    $usage = $this->getJson(route('api.themes.show', $theme))->assertOk()->json('usage');
    expect(collect($usage)->firstWhere('kind', 'live')['version'])->toBe(2);
    $this->get(route('admin.companies.show', $company->uuid))->assertInertia(fn ($page) => $page
        ->where('designWorkspace.liveTheme.version', 2)->where('designWorkspace.liveTheme.latestVersion', 3));
    $publish->execute($company, null, $active->id, $this->staff->id, rollback: true);
    expect($company->publications()->latest('id')->first()->effectiveRevision()->design['theme']['colors'])->toBe($first->colors);
});

it('rejects a version belonging to another theme and keeps library usage permission scoped', function () {
    $base = $this->makeTheme();
    $other = $this->makeTheme();
    $company = $this->makeCompany(['theme_id' => $base->id]);
    $form = [...ClassicDesign::editorSettings($company, null), 'name' => 'Wrong version',
        'theme_version_id' => $other->versions()->sole()->id, 'search_option' => 'bol'];
    $this->post(route('admin.drafts.store', $company->uuid), $form)->assertSessionHasErrors('theme_id');
    $this->actingAs($this->makeUserWithPermission('theme:show'))->getJson(route('api.themes.show', $base))
        ->assertOk()->assertJsonMissingPath('usage');
    $this->actingAs($this->makeUserWithPermission('theme:update'))->getJson(route('api.themes.show', $base))
        ->assertOk()->assertJsonPath('versions.0.version', 1)->assertJsonMissingPath('usage');
    $this->actingAs($this->makeUserWithPermission('image:show'))->getJson(route('api.themes.show', $base))->assertForbidden();
    $this->getJson(route('api.themes.index'))->assertForbidden();
    expect($company->drafts()->count())->toBe(0);
    $this->actingAs($this->staff)->deleteJson(route('api.themes.destroy', $other))->assertSuccessful();
    expect(fn () => $this->makeCompany(['theme_id' => $other->id]))->toThrow(ValidationException::class);
    $this->post(route('admin.drafts.store', $company->uuid), [...$form, 'theme_id' => $other->id])
        ->assertSessionHasErrors('theme_id');
});

it('redirects old theme bookmarks to the Studio library with the original permissions', function () {
    $theme = $this->makeTheme();
    $viewer = $this->makeUserWithPermission('theme:show');
    $creator = $this->makeUserWithPermission('theme:store');
    $this->actingAs($viewer)->get('/admin/themes')->assertRedirectToRoute('admin.studio.styles.index');
    foreach (['theme', 'themes'] as $prefix) {
        $this->get('/admin/'.$prefix.'/'.$theme->uuid)->assertRedirectToRoute('admin.studio.styles.show', $theme->uuid);
    }
    $this->get(route('admin.studio.styles.index'))->assertOk();
    $this->get(route('admin.studio.styles.create'))->assertForbidden();
    $this->get('/admin/themes/create')->assertForbidden();
    $this->actingAs($creator)->get('/admin/themes/create')->assertRedirectToRoute('admin.studio.styles.create');
    $this->get(route('admin.studio.styles.create'))->assertOk();
    $this->get(route('admin.studio.styles.index'))->assertForbidden();
    $this->get(route('admin.studio.styles.show', $theme->uuid))->assertForbidden();
});
