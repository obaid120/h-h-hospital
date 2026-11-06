<?php

namespace App\Services;

use Illuminate\Http\Request;

class BookingSession
{
    public function secret(Request $request): string
    {
        if (! $request->session()->has('booking_owner')) {
            $request->session()->put('booking_owner', hash_hmac('sha256', $request->session()->getId(), config('app.key')));
        }

        return $request->session()->get('booking_owner');
    }
}
