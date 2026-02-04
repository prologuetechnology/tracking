<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_enabled',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_has_feature')
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
