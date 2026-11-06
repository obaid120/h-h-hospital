<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffOperationResponse
{
    public static function success(Request $request, string $url, string $message): JsonResponse|RedirectResponse
    {
        $request->session()->flash('status', $message);

        return $request->expectsJson()
            ? response()->json(['completed' => true, 'redirect_url' => $url], headers: ['Cache-Control' => 'no-store, private'])
            : redirect()->to($url);
    }

    public static function retry(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 503, ['Cache-Control' => 'no-store, private'])
            : back()->withInput()->withErrors([$field => $message]);
    }
}
