<?php

namespace App\Http\Controllers\Checkout;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Checkout\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderPaymentController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->own($request, $order);
        $order->load(['course' => fn ($query) => $query->withTrashed(), 'enrollment']);
        $payment = $order->payments()->latest()->first();
        $firstReadyLesson = $order->status === OrderStatus::Paid && ! $order->course->trashed()
            ? $order->course->lessons()->whereHas('videoAsset', fn ($query) => $query->where('status', 'ready'))
                ->orderBy('sort_order')->first()
            : null;

        return view('checkout.pay', compact('order', 'payment', 'firstReadyLesson'));
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        $this->own($request, $order);
        $payment = $order->payments()->latest()->first();
        return response()->json([
            'status' => $order->status->value,
            'payment_status' => $payment?->status?->value,
            'expires_at' => $order->expires_at?->copy()->utc()->toIso8601String(),
            'paid_at' => $order->paid_at?->copy()->utc()->toIso8601String(),
            'redirect' => $order->status === OrderStatus::Paid ? route('orders.pay', $order) : null,
        ]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $this->own($request, $order);
        $orders->cancel($request->user(), $order);
        return redirect()->route('orders.pay', $order)->with('success', 'Pesanan dibatalkan.');
    }

    private function own(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
