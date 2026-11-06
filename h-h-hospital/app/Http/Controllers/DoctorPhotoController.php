<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorPhotoController extends Controller
{
    public function __invoke(Request $request, Doctor $doctor): StreamedResponse
    {
        $public = Doctor::query()->publiclyVisible()->whereKey($doctor->id)->exists();
        $actor = $request->user()?->fresh();
        $staff = $actor && $actor->is_active && $actor->auth_version === (int) $request->session()->get('staff_auth_version') && Gate::forUser($actor)->allows('view', $doctor);
        abort_unless($public || $staff, 404);
        abort_unless($doctor->photo_path && Storage::disk('public')->exists($doctor->photo_path), 404);

        return Storage::disk('public')->response($doctor->photo_path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store']);
    }
}
