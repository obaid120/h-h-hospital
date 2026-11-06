<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $doctors = Doctor::query()->publiclyVisible()->with(['user', 'weeklySchedules'])->orderBy('id')->paginate(12);

        return view('home', compact('doctors'));
    }
}
