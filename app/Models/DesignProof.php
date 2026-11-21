<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignProof extends Model
{
    use HasUuid;

    protected $fillable = ['snapshot', 'shipment_reference', 'created_by'];

    protected $attributes = ['generation' => 1, 'status' => 'pending'];

    protected $hidden = ['snapshot', 'directory'];

    protected function casts(): array
    {
        return ['generation' => 'integer', 'snapshot' => 'encrypted:array', 'checksums' => 'array', 'approved_at' => 'datetime'];
    }

    public static function companyDirectory(int $companyId): string
    {
        return 'design-proofs/company-'.$companyId;
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(DesignDraftRevision::class, 'design_draft_revision_id');
    }
}
