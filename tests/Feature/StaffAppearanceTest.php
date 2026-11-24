<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCoreFixtures();
});

it('saves only the current staff appearance and returns it on a fresh visit', function () {
    $actor = $this->makeStandardUser();
    $other = $this->makeStandardUser();
    $this->actingAs($actor)->get(route('admin.tracking.index'))->assertInertia(fn (Assert $page) => $page->where('staffAppearance', 'system'));
    $this->from(route('admin.tracking.index'))->patch(route('admin.preferences.appearance'), ['appearance' => 'dark', 'user_id' => $other->id])->assertRedirect(route('admin.tracking.index'));
    $this->get(route('admin.tracking.index'))->assertInertia(fn (Assert $page) => $page->where('staffAppearance', 'dark'));
    expect($actor->fresh()->staff_appearance)->toBe('dark')->and($other->fresh()->staff_appearance)->toBe('system');
    $this->patchJson(route('admin.preferences.appearance'), ['appearance' => 'invalid'])->assertUnprocessable();
    $this->get(route('trackShipment.notFound', 'SAMPLE'))->assertInertia(fn (Assert $page) => $page->where('staffAppearance', null));
});

it('follows the device while signed out and prevents preference changes during impersonation', function () {
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('staffAppearance', 'system'));
    $this->patch(route('admin.preferences.appearance'), ['appearance' => 'dark'])->assertRedirect(route('login'));
    $actor = $this->makeSuperAdmin();
    $target = $this->makeStandardUser();
    $actor->forceFill(['staff_appearance' => 'light'])->save();
    $this->actingAs($target)->withSession(['impersonate_original_id' => $actor->id]);
    $this->get(route('admin.tracking.index'))->assertInertia(fn (Assert $page) => $page->where('staffAppearance', 'light'));
    $this->patch(route('admin.preferences.appearance'), ['appearance' => 'dark'])->assertForbidden();
    expect($target->fresh()->staff_appearance)->toBe('system');
});
