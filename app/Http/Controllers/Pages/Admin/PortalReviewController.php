<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Actions\DesignDrafts\PortalReviewShipment;
use App\Actions\DesignDrafts\RenderProofImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortalReviewStyleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalReviewController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/portalReview/Workbench', ['shipment' => PortalReviewShipment::shipment()]);
    }

    public function asset(string $asset): BinaryFileResponse
    {
        return response()->file(PortalReviewShipment::assetPath($asset), ['Content-Type' => 'image/webp']);
    }

    public function classic(): Response
    {
        $page = PortalReviewShipment::page(classic: true);

        return Inertia::render($page['component'], [...$page['props'], 'isDraftPreview' => true]);
    }

    public function preview(PortalReviewStyleRequest $request): Response
    {
        $page = PortalReviewShipment::page($request->validated());

        return Inertia::render($page['component'], [...$page['props'], 'isDraftPreview' => true]);
    }

    public function proofs(PortalReviewStyleRequest $request, RenderProofImages $renderer): JsonResponse
    {
        $page = PortalReviewShipment::page($request->validated());
        $page['props']['frozenAt'] = '2026-11-20T16:15:00Z';
        $images = [];
        foreach (array_keys($page['props']['brandImageUrls']) as $path) {
            $images[$path] = base64_encode(File::get(PortalReviewShipment::assetPath(pathinfo($path, PATHINFO_FILENAME))));
        }
        $directory = storage_path('framework/cache/portal-review/'.Str::uuid());
        File::ensureDirectoryExists($directory, 0700);
        try {
            $hashes = $renderer->render($page, $images, $directory);
            $views = [];
            foreach ($hashes as $view => $hash) {
                $views[$view] = ['sha256' => $hash, 'png' => base64_encode(File::get("{$directory}/{$view}.png"))];
            }

            return response()->json(['views' => $views]);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
