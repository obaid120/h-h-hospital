<?php

namespace App\Http\Controllers;

use App\Http\Requests\HospitalSettingsRequest;
use App\Services\HospitalSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class HospitalSettingsController extends Controller
{
    public function edit(HospitalSettings $settings): View
    {
        return view('staff.admin.settings', ['settings' => $settings->current()]);
    }

    public function update(HospitalSettingsRequest $request, HospitalSettings $settings): RedirectResponse
    {
        try {
            $settings->save($request->user(), $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return back()->withInput()->withErrors(['settings' => __('admin.settings_retry')]);
        }

        return to_route('staff.admin.settings.edit')->with('status', __('admin.settings_saved'));
    }
}
