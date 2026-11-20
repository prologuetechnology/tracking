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
        $limit = (int) ini_get('max_execution_time');
        $sync = config('queue.connections.'.config('queue.default').'.driver') === 'sync';
        $extend = $sync && $limit > 0 && $limit <= $job->timeout && function_exists('set_time_limit');
        try {
            if ($extend) {
                set_time_limit($job->timeout + 10);
            }
            Bus::dispatch($job);
        } catch (Throwable $exception) {
            $job->failed($exception);
            Log::warning('Proof export could not be queued or completed.', ['proof_id' => $proof->id, 'exception' => $exception::class]);
        } finally {
            if ($extend) {
                set_time_limit($limit);
            }
        }
    }
}
