<?php

namespace App\Http\Controllers\Api;

use App\Actions\Themes\ResolveThemeStyle;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewThemeStyleRequest;
use Illuminate\Http\JsonResponse;

class PreviewThemeStyleController extends Controller
{
    public function __invoke(PreviewThemeStyleRequest $request): JsonResponse
    {
        $style = $request->validated('style');
        $derived = ResolveThemeStyle::attributes([...$style, 'overrides' => []]);

        return response()->json([
            'derived' => $derived['colors']['root'],
            'appearance' => ResolveThemeStyle::attributes($style),
        ]);
    }
}
