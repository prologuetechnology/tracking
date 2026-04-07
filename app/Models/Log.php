<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $level
 * @property string $message
 * @property string|null $context
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Log whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'message',
        'context',
    ];
}
