<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int $image_type_id
 * @property string $file_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read ImageType|null $imageType
 *
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
 *
 * @mixin \Eloquent
 */
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory, HasUuid;

    protected static function booted(): void
    {
        static::deleting(function (Image $image): void {
            if (Company::query()->where('logo_image_id', $image->id)
                ->orWhere('banner_image_id', $image->id)->orWhere('footer_image_id', $image->id)->exists()) {
                throw ValidationException::withMessages(['image' => 'A live tracking page uses this image. Replace it through an approved design draft first.']);
            }
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
