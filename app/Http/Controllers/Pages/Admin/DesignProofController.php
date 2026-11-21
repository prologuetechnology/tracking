<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\QueueDesignProof;
use App\Actions\DesignDrafts\TrackingDesignPage;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DesignDraft;
use App\Models\DesignDraftRevision;
use App\Models\DesignProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignProofController extends Controller
{
    public function store(Company $company, DesignDraft $draft, DesignDraftRevision $revision, Request $request, TrackingDesignPage $pages, QueueDesignProof $queue): RedirectResponse
    {
        $lookup = $request->validate([
            'trackingNumber' => ['required', 'string', 'max:128'],
            'searchOption' => ['required', Rule::in(['bol', 'carrierPro'])],
        ]);
        $page = $pages->preview($company, $revision, $lookup);
        if (! $page) {
            throw ValidationException::withMessages(['trackingNumber' => 'No shipment was available for this company and reference.']);
        }
        $page['props']['frozenAt'] = now()->toIso8601String();
        $page['props']['app'] = [
            'name' => config('app.name'), 'env' => 'production',
            'disks' => ['spaces' => [
                'bucket' => config('filesystems.disks.spaces.bucket'),
                'region' => config('filesystems.disks.spaces.region'),
            ]],
        ];
        $proof = $revision->proofs()->create([
            'created_by' => $request->user()->id,
            'shipment_reference' => $lookup['trackingNumber'],
            'snapshot' => $page,
        ]);
        $queue->execute($proof);

        return to_route('admin.drafts.preview', [$company->uuid, $draft->uuid, $revision->version]);
    }

    public function retry(Company $company, DesignDraft $draft, DesignDraftRevision $revision, DesignProof $proof, QueueDesignProof $queue): RedirectResponse
    {
        $updated = DesignProof::whereKey($proof->id)->where('generation', $proof->generation)
            ->where(fn ($q) => $q->where('status', 'failed')->orWhere(fn ($q) => $q->where('status', 'pending')->where('updated_at', '<', now()->subMinutes(10))))
            ->update(['status' => 'pending', 'generation' => $proof->generation + 1]);
        if ($updated) {
            $queue->execute($proof->refresh());
        }

        return to_route('admin.drafts.preview', [$company->uuid, $draft->uuid, $revision->version]);
    }

    public function download(Company $company, DesignDraft $draft, DesignDraftRevision $revision, DesignProof $proof, string $view): StreamedResponse
    {
        abort_unless($proof->status === 'ready', 404);

        $stream = Storage::disk('local')->readStream($proof->directory.'/'.$view.'.png');
        abort_unless(is_resource($stream), 503, 'The archived image is temporarily unavailable.');
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        if (! hash_equals($proof->checksums[$view], hash_final($hash)) || ! rewind($stream)) {
            fclose($stream);
            abort(503, 'The archived image could not be verified.');
        }

        return response()->streamDownload(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, "revision-{$revision->version}-{$proof->uuid}-{$view}.png", ['Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store']);
    }

    public function approve(Company $company, DesignDraft $draft, DesignDraftRevision $revision, DesignProof $proof, Request $request): RedirectResponse
    {
        $input = $request->validate(['customer_approver' => ['required', 'string', 'max:120']]);
        abort_unless($proof->status === 'ready', 409);
        DesignProof::whereKey($proof->id)->whereNull('approved_at')->update([
            'approved_by' => $request->user()->id, 'customer_approver' => $input['customer_approver'], 'approved_at' => now(),
        ]);

        return to_route('admin.drafts.preview', [$company->uuid, $draft->uuid, $revision->version]);
    }
}
