<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingPublication extends Model
{
    protected $fillable = ['design_proof_id', 'baseline_revision_id', 'action', 'created_by'];

    public function baselineRevision(): BelongsTo
    {
        return $this->belongsTo(DesignDraftRevision::class, 'baseline_revision_id');
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(DesignProof::class, 'design_proof_id');
    }
}
