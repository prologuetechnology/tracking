<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ThemeVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['version', 'name', 'colors', 'radius', 'style'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Saved theme versions cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Saved theme versions cannot be removed.'));
    }

    protected function casts(): array
    {
        return ['version' => 'integer', 'colors' => 'array', 'style' => 'array'];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class)->withTrashed();
    }

    public function snapshot(): array
    {
        return [
            'id' => $this->theme_id, 'theme_version_id' => $this->id,
            ...$this->only(['version', 'name', 'colors', 'radius', 'style']),
        ];
    }
}
