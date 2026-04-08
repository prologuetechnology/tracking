<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'colors',
        'radius',
        'is_system',
        'derive_from',
    ];

    protected $casts = [
        'colors' => 'array',
        'is_system' => 'boolean',
    ];
}
