<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\CompanyApiTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property string $api_token
 * @property int $is_valid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company|null $company
 *
 * @method static \Database\Factories\CompanyApiTokenFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereIsValid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereUuid($value)
 *
 * @property string $bol
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyApiToken whereBol($value)
 *
 * @mixin \Eloquent
 */
class CompanyApiToken extends Model
{
    /** @use HasFactory<CompanyApiTokenFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'api_token',
        'bol',
        'is_valid',
        'created_at',
        'updated_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
