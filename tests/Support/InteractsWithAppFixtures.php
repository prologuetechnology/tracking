<?php

namespace Tests\Support;

use App\Enums\ImageTypeEnum;
use App\Enums\RoleEnum;
use App\Models\AllowedDomain;
use App\Models\Company;
use App\Models\CompanyApiToken;
use App\Models\Image;
use App\Models\ImageType;
use App\Models\Theme;
use App\Models\User;
use App\Support\Testing\FakePipelinePayloads;
use Database\Seeders\ImageTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

trait InteractsWithAppFixtures
{
    protected function seedCoreFixtures(bool $withImageTypes = false): void
    {
        $seeders = [RolesAndPermissionsSeeder::class];

        if ($withImageTypes) {
            $seeders[] = ImageTypeSeeder::class;
        }

        $this->seed($seeders);
    }

    protected function makeSuperAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->syncRoles([RoleEnum::SUPER_ADMIN->value]);

        return $user;
    }

    protected function makeCompanyAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->syncRoles([RoleEnum::COMPANY_ADMIN->value]);

        return $user;
    }

    protected function makeStandardUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->syncRoles([RoleEnum::STANDARD->value]);

        return $user;
    }

    protected function makeUserWithPermission(string $permission, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->givePermissionTo($permission);

        return $user;
    }

    protected function makeTheme(array $attributes = []): Theme
    {
        return Theme::factory()->create($attributes);
    }

    protected function makeCompany(array $attributes = []): Company
    {
        return Company::factory()->create($attributes);
    }

    protected function makeImageType(string $name = ImageTypeEnum::LOGO->value): ImageType
    {
        return ImageType::query()->firstOrCreate(['name' => $name]);
    }

    protected function findImageTypeFixture(string $name): ImageType
    {
        return ImageType::query()->where('name', $name)->firstOrFail();
    }

    protected function makeImage(string $type = ImageTypeEnum::LOGO->value, array $attributes = []): Image
    {
        $imageType = $this->makeImageType($type);

        return Image::factory()
            ->for($imageType, 'imageType')
            ->create($attributes)
            ->load('imageType');
    }

    protected function makeCompanyApiToken(Company $company, array $attributes = []): CompanyApiToken
    {
        return CompanyApiToken::factory()
            ->for($company)
            ->create($attributes);
    }

    protected function makeAllowedDomain(?User $user = null, array $attributes = []): AllowedDomain
    {
        $user ??= $this->makeSuperAdmin();

        return AllowedDomain::factory()->create([
            'created_by' => $user->id,
            'updated_by' => $user->id,
            ...$attributes,
        ]);
    }

    protected function fakeTrackingApisFixture(?Company $company = null, string $trackingNumber = 'BOL123'): void
    {
        $companyId = $company?->pipeline_company_id ?? 1001;
        $appUrl = rtrim(config('app.url'), '/');

        Http::fake([
            rtrim((string) config('services.pipeline.api_url'), '/').'/shipmentSearch' => Http::response(
                FakePipelinePayloads::shipmentSearch($trackingNumber, $companyId),
                200,
            ),
            rtrim((string) config('services.pipeline.base_url'), '/').'/app.php?r=mapApi/getRoutes*' => Http::response(
                FakePipelinePayloads::shipmentCoordinates(),
                200,
            ),
            rtrim((string) config('services.pipeline.api_url'), '/').'/Execute/GetDocuments' => Http::response(
                FakePipelinePayloads::shipmentDocuments($appUrl, $trackingNumber),
                200,
            ),
            "{$appUrl}/testing/fake-pipeline/documents/*" => Http::response('', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => '1024',
                'Last-Modified' => 'Wed, 06 Mar 2024 12:00:00 GMT',
            ]),
        ]);
    }

    protected function syncRole(string $roleName): Role
    {
        return Role::findByName($roleName);
    }
}
