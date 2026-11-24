<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property array $colors
 * @property string|null $radius
 * @property bool $is_system
 * @property string $derive_from
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\ThemeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereColors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereDeriveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'colors',
        'style',
        'radius',
        'is_system',
        'derive_from',
    ];

    protected static function booted(): void
    {
        static::created(fn (Theme $theme) => $theme->appendVersion(1));
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ThemeVersion::class)->orderByDesc('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ThemeVersion::class)->latestOfMany('version');
    }

    public function appendVersion(int $number): ThemeVersion
    {
        return $this->versions()->create([...$this->only(['name', 'colors', 'radius', 'style']), 'version' => $number]);
    }

    protected $casts = [
        'colors' => 'array',
        'style' => 'array',
        'is_system' => 'boolean',
    ];
}
