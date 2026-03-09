<?php

namespace App\Models;

use App\Enums\ImageTypeEnum;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int $image_type_id
 * @property string $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\ImageType|null $imageType
 * @method static \Database\Factories\ImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereImageTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUuid($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory, HasUuid;

    protected static function booted(): void
    {
        static::deleting(function (Image $image) {
            $type = $image->loadMissing('imageType')->imageType?->name;

            match ($type) {
                ImageTypeEnum::LOGO->value => Company::query()->where('logo_image_id', $image->id)->update(['logo_image_id' => null]),
                ImageTypeEnum::BANNER->value => Company::query()->where('banner_image_id', $image->id)->update(['banner_image_id' => null]),
                ImageTypeEnum::FOOTER->value => Company::query()->where('footer_image_id', $image->id)->update(['footer_image_id' => null]),
                default => null,
            };
        });
    }

    protected $fillable = [
        'name',
        'file_path',
        'image_type_id',
    ];

    public function imageType(): BelongsTo
    {
        return $this->belongsTo(ImageType::class, 'image_type_id');
    }

    public function logoCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'logo_image_id');
    }

    public function bannerCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'banner_image_id');
    }

    public function footerCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'footer_image_id');
    }

    public function companyUsageCount(): int
    {
        $logoCount = $this->getAttribute('logo_companies_count');
        $bannerCount = $this->getAttribute('banner_companies_count');
        $footerCount = $this->getAttribute('footer_companies_count');

        if ($logoCount !== null || $bannerCount !== null || $footerCount !== null) {
            return (int) $logoCount + (int) $bannerCount + (int) $footerCount;
        }

        return $this->logoCompanies()->count()
            + $this->bannerCompanies()->count()
            + $this->footerCompanies()->count();
    }
}
