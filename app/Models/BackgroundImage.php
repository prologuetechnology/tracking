<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $url
 * @property int $is_system
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Company> $companies
 * @property-read int|null $companies_count
 *
 * @method static \Database\Factories\BackgroundImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundImage whereUuid($value)
 *
 * @mixin \Eloquent
 */
class BackgroundImage extends Model
{
    /** @use HasFactory<\Database\Factories\BackgroundFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'url',
        'is_system',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
