<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignDraftRevision extends Model
{
    protected $fillable = [
        'version', 'created_by', 'design', 'search_option', 'tracking_number',
        'logo_image_id', 'banner_image_id', 'footer_image_id',
    ];

    protected function casts(): array
    {
        return ['design' => 'array', 'version' => 'integer'];
    }
}
