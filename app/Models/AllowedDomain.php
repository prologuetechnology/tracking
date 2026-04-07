<?php

namespace App\Models;

use Database\Factories\AllowedDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $domain
 * @property string|null $favicon_url
 * @property string|null $description
 * @property int $is_active
 * @property int $created_by
 * @property int $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $createdBy
 * @property-read User|null $updatedBy
 *
 * @method static \Database\Factories\AllowedDomainFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereFaviconUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowedDomain withoutTrashed()
 *
 * @mixin \Eloquent
 */
class AllowedDomain extends Model
{
    /** @use HasFactory<AllowedDomainFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'domain',
        'favicon_url',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
