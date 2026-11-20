<?php

namespace App\Jobs;

use App\Actions\DesignDrafts\RenderProofImages;
use App\Models\DesignProof;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RenderDesignProof implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 110;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $proofId, public readonly int $generation = 1) {}

    public function handle(RenderProofImages $renderer): void
    {
        $proof = DesignProof::with('revision:id,design_draft_id', 'revision.draft:id,company_id')->find($this->proofId);
        if ($proof?->status !== 'pending' || $proof->generation !== $this->generation) {
            return;
        }
        $directory = DesignProof::companyDirectory($proof->revision->draft->company_id).'/'.$proof->uuid.'/'.Str::uuid();
        $path = Storage::disk('local')->path($directory);
        File::ensureDirectoryExists($path, 0700, true);
        $saved = false;
        try {
            $hashes = $renderer->execute($proof->snapshot, $path);
            $saved = DesignProof::whereKey($proof->id)->where('generation', $this->generation)->where('status', 'pending')->update([
                'directory' => $directory,
                'checksums' => json_encode($hashes, JSON_THROW_ON_ERROR),
                'status' => 'ready',
            ]) === 1;
        } finally {
            if (! $saved) {
                File::deleteDirectory($path);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        DesignProof::whereKey($this->proofId)->where('generation', $this->generation)->where('status', 'pending')->update(['status' => 'failed']);
    }
}
