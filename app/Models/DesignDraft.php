<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DesignDraft extends Model
{
    use HasUuid;

    protected $fillable = ['name'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(DesignDraftRevision::class)->latestOfMany('version');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DesignDraftRevision::class);
    }
}
