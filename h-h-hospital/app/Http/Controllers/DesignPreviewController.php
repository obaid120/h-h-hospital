<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DesignPreviewController extends Controller
{
    public function staff(Request $request): View
    {
        $this->ensurePreviewEnvironment();

        return view('design.staff', ['visits' => $this->sampleVisits($request)]);
    }

    public function components(Request $request): View
    {
        $this->ensurePreviewEnvironment();

        return view('design.components', ['visits' => $this->sampleVisits($request)]);
    }

    public function checkForm(Request $request): RedirectResponse
    {
        $this->ensurePreviewEnvironment();

        $request->validate([
            'patient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'department' => ['required', 'in:general,specialist'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return to_route('design.components')->with('status', __('ui.library.checked'));
    }

    private function ensurePreviewEnvironment(): void
    {
        abort_unless(app()->environment('local', 'testing'), 404);
    }

    private function sampleVisits(Request $request): LengthAwarePaginator
    {
        $samples = collect([
            ['name' => 'Ayesha Khan', 'initials' => 'AK', 'reference' => 'DEMO-1001', 'doctor' => 'Dr. Sara Malik', 'status' => 'pending', 'type' => 'normal'],
            ['name' => 'Ahmed Raza', 'initials' => 'AR', 'reference' => 'DEMO-1002', 'doctor' => 'Dr. Hassan Ali', 'status' => 'confirmed', 'type' => 'priority'],
            ['name' => 'Fatima Noor', 'initials' => 'FN', 'reference' => 'DEMO-1003', 'doctor' => 'Dr. Sara Malik', 'status' => 'completed', 'type' => 'normal'],
            ['name' => 'Bilal Ahmed', 'initials' => 'BA', 'reference' => 'DEMO-1004', 'doctor' => 'Dr. Hassan Ali', 'status' => 'confirmed', 'type' => 'normal'],
            ['name' => 'Maryam Iqbal', 'initials' => 'MI', 'reference' => 'DEMO-1005', 'doctor' => 'Dr. Sara Malik', 'status' => 'pending', 'type' => 'normal'],
            ['name' => 'Usman Tariq', 'initials' => 'UT', 'reference' => 'DEMO-1006', 'doctor' => 'Dr. Hassan Ali', 'status' => 'cancelled', 'type' => 'normal'],
        ]);
        $perPage = 4;
        $page = max(1, min((int) ceil($samples->count() / $perPage), $request->integer('page', 1)));

        return new LengthAwarePaginator(
            $samples->forPage($page, $perPage)->values(),
            $samples->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')],
        );
    }
}
