<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function show(Request $request, Payment $payment): View
    {
        Gate::authorize('view', $payment);
        $payment->load('appointment.cancellation');
        $refundedMinor = (int) $payment->refunds()->sum('amount_minor');
        $refunds = $payment->refunds()->orderByDesc('id')->paginate(10, ['*'], 'refund_page')->withQueryString();

        return view('staff.reception.receipt', ['payment' => $payment, 'printView' => $request->routeIs('staff.receipts.print'), 'refundedMinor' => $refundedMinor, 'refunds' => $refunds, 'refundKey' => (string) Str::uuid()]);
    }
}
