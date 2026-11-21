<?php

namespace App\Actions\DesignDrafts;

use App\Models\TrackingPublication;

final class LiveDesignSummary
{
    public static function forPublication(?TrackingPublication $publication): array
    {
        if (! $publication?->design_proof_id) {
            return ['label' => 'Original Classic', 'layout' => ClassicDesign::LAYOUT, 'publicationId' => $publication?->id];
        }
        $revision = $publication->effectiveRevision();

        return [
            'label' => $revision->draft->name.' · Revision '.$revision->version,
            'layout' => $revision->design['layout'],
            'publicationId' => $publication->id,
        ];
    }
}
