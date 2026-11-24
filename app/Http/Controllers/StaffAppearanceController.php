<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStaffAppearanceRequest;
use Illuminate\Http\RedirectResponse;

class StaffAppearanceController extends Controller
{
    public function __invoke(UpdateStaffAppearanceRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['staff_appearance' => $request->validated('appearance')])->save();

        return back(303);
    }
}
