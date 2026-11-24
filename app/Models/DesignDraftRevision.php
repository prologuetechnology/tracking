<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignDraftRevision extends Model
{
    protected $fillable = [
        'version', 'created_by', 'design', 'search_option', 'tracking_number',
        'logo_image_id', 'banner_image_id', 'footer_image_id', 'theme_version_id',
    ];

    public function approve(int $staffId): void
    {
        self::whereKey($this->id)->whereNull('approved_at')->update(['approved_at' => now(), 'approved_by' => $staffId]);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(DesignProof::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(DesignDraft::class, 'design_draft_id');
    }

    protected function casts(): array
    {
        return ['design' => 'array', 'version' => 'integer', 'approved_at' => 'datetime'];
    }
}
