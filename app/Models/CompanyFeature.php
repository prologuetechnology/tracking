<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $default_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Company> $companies
 * @property-read int|null $companies_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereDefaultEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyFeature whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CompanyFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_enabled',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_has_feature')
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
