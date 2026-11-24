<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingPublication extends Model
{
    protected $fillable = ['published_revision_id', 'design_proof_id', 'baseline_revision_id', 'action', 'created_by'];

    public function effectiveRevision(): DesignDraftRevision
    {
        return $this->publishedRevision ?? $this->baselineRevision;
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(DesignDraftRevision::class, 'published_revision_id');
    }

    public function baselineRevision(): BelongsTo
    {
        return $this->belongsTo(DesignDraftRevision::class, 'baseline_revision_id');
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(DesignProof::class, 'design_proof_id');
    }
}
