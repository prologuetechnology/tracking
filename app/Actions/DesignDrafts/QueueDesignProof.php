<?php

namespace App\Actions\DesignDrafts;

use App\Jobs\RenderDesignProof;
use App\Models\DesignProof;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueDesignProof
{
    public function execute(DesignProof $proof): void
    {
        $job = new RenderDesignProof($proof->id, $proof->generation);
        try {
            Bus::dispatch($job);
        } catch (Throwable $exception) {
            $job->failed($exception);
            Log::warning('Proof export could not be queued or completed.', ['proof_id' => $proof->id, 'exception' => $exception::class]);
        }
    }
}
