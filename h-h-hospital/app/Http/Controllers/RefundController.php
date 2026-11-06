<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundRequest;
use App\Models\Payment;
use App\Services\PaymentRefundService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RefundController extends Controller
{
    public function store(RefundRequest $request, Payment $payment, PaymentRefundService $service): JsonResponse|RedirectResponse
    {
        try {
            $refund = $service->record($request->user(), $payment, $request->validated());
        } catch (QueryException|\PDOException $exception) {
            report($exception);

            return StaffOperationResponse::retry($request, 'refund', __('cancellations.errors.retry'));
        }

        return StaffOperationResponse::success($request, route('staff.receipts.show', $payment), $refund->wasRecentlyCreated ? __('cancellations.refund_recorded') : __('cancellations.refund_replayed'));
    }
}
