<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Database\Factories\ImageTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageType whereUuid($value)
 * @mixin \Eloquent
 */
class ImageType extends Model
{
    /** @use HasFactory<\Database\Factories\ImageTypeFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
    ];
}
