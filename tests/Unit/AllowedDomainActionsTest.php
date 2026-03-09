<?php

namespace Tests\Unit;

use App\Actions\AllowedDomains\CreateAllowedDomain;
use App\Actions\AllowedDomains\ListAllowedDomains;
use App\Actions\AllowedDomains\ToggleAllowedDomainStatus;
use App\Actions\AllowedDomains\UpdateAllowedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowedDomainActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreFixtures();
    }

    public function test_it_creates_and_updates_allowed_domains_with_metadata(): void
    {
        $user = $this->makeSuperAdmin();
        $create = new CreateAllowedDomain;
        $update = new UpdateAllowedDomain;

        $domain = $create->execute([
            'domain' => 'example.com',
            'description' => 'Initial',
            'is_active' => true,
        ], $user);

        $this->assertSame('https://www.google.com/s2/favicons?domain=example.com', $domain->favicon_url);
        $this->assertSame($user->id, $domain->created_by);

        $updated = $update->execute($domain, [
            'domain' => 'acme.com',
            'description' => 'Updated',
        ], $user);

        $this->assertSame('acme.com', $updated->domain);
        $this->assertSame('https://www.google.com/s2/favicons?domain=acme.com', $updated->favicon_url);
        $this->assertSame($user->id, $updated->updated_by);
    }

    public function test_it_lists_domains_by_domain_and_toggles_active_status(): void
    {
        $user = $this->makeSuperAdmin();
        $beta = $this->makeAllowedDomain($user, ['domain' => 'beta.com', 'is_active' => true]);
        $alpha = $this->makeAllowedDomain($user, ['domain' => 'alpha.com', 'is_active' => false]);

        $listed = (new ListAllowedDomains)->execute();

        $this->assertSame(['alpha.com', 'beta.com'], $listed->pluck('domain')->all());

        $toggled = (new ToggleAllowedDomainStatus)->execute($alpha);

        $this->assertEquals(1, $toggled->is_active);
        $this->assertSame($beta->id, $beta->fresh()->id);
    }
}
