<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property int $is_active
 * @property string $name
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $email
 * @property int $pipeline_company_id
 * @property int|null $logo_image_id
 * @property int|null $banner_image_id
 * @property int|null $footer_image_id
 * @property int|null $theme_id
 * @property int $enable_map
 * @property int $enable_documents
 * @property int $requires_brand
 * @property string|null $brand
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read CompanyApiToken|null $apiToken
 * @property-read \App\Models\Image|null $banner
 * @property-read \App\Models\Image|null $footer
 * @property-read \App\Models\Image|null $logo
 * @property-read \App\Models\Theme|null $theme
 * @method static CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereBannerImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEnableMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereFooterImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereLogoImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePipelineCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereRequiresBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereThemeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereWebsite($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompanyFeature> $features
 * @property-read int|null $features_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEnableDocuments($value)
 * @mixin \Eloquent
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'logo',
        'website',
        'phone',
        'email',
        'pipeline_company_id',
        'logo_image_id',
        'banner_image_id',
        'footer_image_id',
        'theme_id',
        'enable_map',
        'enable_documents',
        'requires_brand',
        'brand',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_map' => 'boolean',
        'enable_documents' => 'boolean',
        'requires_brand' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Company $company) {
            if (! Schema::hasTable('company_features') || ! Schema::hasTable('company_has_feature')) {
                return;
            }

            $defaultFeatureIds = CompanyFeature::query()
                ->where('default_enabled', true)
                ->pluck('id')
                ->all();

            if (! empty($defaultFeatureIds)) {
                $company->features()->syncWithoutDetaching($defaultFeatureIds);
            }
        });

        static::saved(function (Company $company) {
            if (! Schema::hasTable('company_features') || ! Schema::hasTable('company_has_feature')) {
                return;
            }

            $featureMap = [
                'enable_map' => 'enable_map',
                'enable_documents' => 'enable_documents',
            ];

            foreach ($featureMap as $field => $slug) {
                if (! $company->wasChanged($field)) {
                    continue;
                }

                $enabled = (bool) $company->getAttribute($field);

                if ($enabled) {
                    $company->enableFeature($slug);
                } else {
                    $company->disableFeature($slug);
                }
            }
        });
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'logo_image_id');
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'banner_image_id');
    }

    public function footer(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'footer_image_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(CompanyFeature::class, 'company_has_feature')
            ->withTimestamps();
    }

    public function apiToken(): HasOne
    {
        return $this->hasOne(CompanyApiToken::class);
    }

    public static function booleanFields(): array
    {
        return [
            'enable_map',
            'enable_documents',
            'requires_brand',
        ];
    }

    public static function featureBackedBooleanFields(): array
    {
        return [
            'enable_map',
            'enable_documents',
        ];
    }

    public function syncLegacyFeatureColumns(array $slugs): void
    {
        $updates = [];
        foreach (self::featureBackedBooleanFields() as $field) {
            $updates[$field] = in_array($field, $slugs, true);
        }

        $this->forceFill($updates)->saveQuietly();
    }

    public function syncLegacyFeatureColumn(string $slug, bool $enabled): void
    {
        if (! in_array($slug, self::featureBackedBooleanFields(), true)) {
            return;
        }

        $this->forceFill([$slug => $enabled])->saveQuietly();
    }

    public function hasFeature(string $slug): bool
    {
        if ($this->relationLoaded('features')) {
            return $this->features->contains('slug', $slug);
        }

        return $this->features()->where('slug', $slug)->exists();
    }

    public function hasAnyFeature(array $slugs): bool
    {
        if ($this->relationLoaded('features')) {
            return $this->features->whereIn('slug', $slugs)->isNotEmpty();
        }

        return $this->features()->whereIn('slug', $slugs)->exists();
    }

    public function hasAllFeatures(array $slugs): bool
    {
        if (empty($slugs)) {
            return true;
        }

        if ($this->relationLoaded('features')) {
            $available = $this->features->pluck('slug')->unique()->all();

            return empty(array_diff($slugs, $available));
        }

        $count = $this->features()->whereIn('slug', $slugs)->count();

        return $count === count(array_unique($slugs));
    }

    public function enableFeature(string|array $slugs): void
    {
        $slugs = is_array($slugs) ? $slugs : [$slugs];

        if (empty($slugs)) {
            return;
        }

        $featureIds = CompanyFeature::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        if (! empty($featureIds)) {
            $this->features()->syncWithoutDetaching($featureIds);
        }
    }

    public function disableFeature(string|array $slugs): void
    {
        $slugs = is_array($slugs) ? $slugs : [$slugs];

        if (empty($slugs)) {
            return;
        }

        $featureIds = CompanyFeature::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        if (! empty($featureIds)) {
            $this->features()->detach($featureIds);
        }
    }

    public function syncFeatures(array $slugs): void
    {
        $featureIds = CompanyFeature::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        $this->features()->sync($featureIds);
    }

    public static function findByIdentifier(?string $brand = null, ?int $companyId = null, ?int $pipelineCompanyId = null): ?self
    {
        try {
            $query = self::query()
                ->where('is_active', true)
                ->with(['logo', 'banner', 'footer', 'theme', 'apiToken', 'features']);

            switch (true) {
                case $brand:
                    $company = $query->whereRaw('BINARY `brand` = ?', [$brand])->first();

                    if ($company->pipeline_company_id !== $pipelineCompanyId) {
                        return $company = null;
                    }

                    break;
                case $companyId:
                    $company = $query->where('pipeline_company_id', $companyId)->first();

                    if ($company->pipeline_company_id !== $pipelineCompanyId) {
                        return $company = null;
                    }

                    break;
                case $pipelineCompanyId:
                    $company = $query->where('pipeline_company_id', $pipelineCompanyId)->first();

                    break;
                default:
                    return null;
            }

            if ($company && $company->requires_brand && ! $brand) {
                return null;
            }

            return $company;
        } catch (\Exception $e) {
            Log::channel('database')->error('Error finding company by identifier: '.$e->getMessage());

            return null;
        }
    }
}
