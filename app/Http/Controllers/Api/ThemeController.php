<?php

namespace App\Http\Controllers\Api;

use App\Actions\Themes\CreateTheme;
use App\Actions\Themes\ListThemes;
use App\Actions\Themes\UpdateTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteThemeRequest;
use App\Http\Requests\StoreThemeRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Http\Resources\ThemeResource;
use App\Models\Theme;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends Controller
{
    public function __construct(
        private readonly CreateTheme $createTheme,
        private readonly ListThemes $listThemes,
        private readonly UpdateTheme $updateTheme,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            ThemeResource::collection($this->listThemes->execute()),
            Response::HTTP_OK,
        );
    }

    public function store(StoreThemeRequest $request): JsonResponse
    {
        $theme = $this->createTheme->execute($request->validated());

        return response()->json(ThemeResource::make($theme), Response::HTTP_CREATED);
    }

    public function show(Theme $theme): JsonResponse
    {
        return response()->json(ThemeResource::make($theme), Response::HTTP_OK);
    }

    public function update(UpdateThemeRequest $request, Theme $theme): JsonResponse
    {
        $theme = $this->updateTheme->execute($theme, $request->validated());

        return response()->json(ThemeResource::make($theme), Response::HTTP_OK);
    }

    public function destroy(Theme $theme, DeleteThemeRequest $request): JsonResponse
    {
        $theme->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
