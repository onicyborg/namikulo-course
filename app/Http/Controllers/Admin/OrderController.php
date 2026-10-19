<?php

namespace App\Http\Controllers\Admin;

use App\Services\ServerTable;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Checkout\PaymentEmailService;
use App\Services\Checkout\PaymentInstructionEmailService;
use App\Services\Checkout\PaymentService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request, ServerTable $table): View
    {
        return $this->listing($request, $table, false);
    }

    public function data(Request $request, ServerTable $table): JsonResponse
    {
        return $this->listing($request, $table, true);
    }

    private function listing(Request $request, ServerTable $table, bool $asData): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,paid,expired,cancelled,failed,refunded'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $orders = Order::query();
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $orders->where(fn ($query) => $query->whereRaw('LOWER(orders.order_number) LIKE ?', [$term])
                ->orWhereRaw('LOWER(orders.course_title) LIKE ?', [$term])
                ->orWhereHas('user', fn ($user) => $user->whereRaw('LOWER(users.email) LIKE ?', [$term])));
        }
        if ($filters['status'] ?? null) $orders->where('status', $filters['status']);

        if ($asData) return $table->response($request,
            $orders->with('user:id,name,email')->withCount('payments'),
            ['number', 'member', 'course', 'amount', 'status', 'attempts', 'created', 'actions'],
            'admin.orders._cell', 'order', [0 => 'orders.order_number', 2 => 'orders.course_title', 3 => 'orders.amount', 4 => 'orders.status', 6 => 'orders.created_at'],
            'orders.created_at', 'desc', Order::query()->count());

        return view('admin.orders.index', [
            'orders' => $orders->with(['user:id,name,email'])->withCount('payments')
                ->orderByDesc('orders.created_at')->orderBy('orders.id')
                ->paginate((int) ($filters['per_page'] ?? 10))->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'course', 'enrollment', 'payments' => fn ($query) => $query->latest()]);

        return view('admin.orders.show', compact('order'));
    }

    public function sync(Request $request, Order $order, PaymentService $payments, ActivityLogger $logger): RedirectResponse
    {
        $payment = $order->payments()->latest()->first();
        if (! $payment) return back()->withErrors(['payment' => 'Belum ada percobaan pembayaran.']);
        try {
            $result = $payments->sync($payment);
            $logger->log('synced', $request->user(), 'orders', $order->id, null, ['result' => $result]);
            return back()->with('success', 'Status pembayaran telah diperiksa ke gateway.');
        } catch (\Throwable) {
            return back()->withErrors(['payment' => 'Status belum dapat dipastikan. Coba lagi nanti.']);
        }
    }

    public function retryEmail(Request $request, Order $order, PaymentEmailService $email, ActivityLogger $logger): RedirectResponse
    {
        if ($order->status !== OrderStatus::Paid || $order->payment_email_sent_at) {
            return back()->withErrors(['email' => 'Email hanya dapat dikirim ulang untuk pesanan lunas yang belum tercatat terkirim.']);
        }
        $sent = $email->sendOnce($order->id, true);
        $logger->log('email_retry', $request->user(), 'orders', $order->id, null, ['sent' => $sent]);
        return $sent ? back()->with('success', 'Email konfirmasi berhasil dikirim.')
            : back()->withErrors(['email' => 'Hasil pengiriman belum pasti. Periksa log dan kotak masuk sebelum mencoba lagi.']);
    }

    public function retryInstructionsEmail(Request $request, Order $order, Payment $payment, PaymentInstructionEmailService $email, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($payment->order_id === $order->id, 404);
        if ($order->status !== OrderStatus::Pending
            || $payment->status !== PaymentStatus::Pending
            || $payment->expires_at?->isPast()
            || $payment->instruction_email_sent_at) {
            return back()->withErrors(['email' => 'Instruksi hanya dapat dikirim ulang untuk tagihan aktif yang belum tercatat terkirim.']);
        }
        $sent = $email->sendOnce($payment->id, true);
        $logger->log('email_retry', $request->user(), 'payments', $payment->id, null, ['sent' => $sent, 'email' => 'payment_instructions']);

        return $sent ? back()->with('success', 'Email instruksi pembayaran berhasil dikirim.')
            : back()->withErrors(['email' => 'Hasil pengiriman belum pasti. Periksa kotak masuk dan log sebelum mencoba lagi.']);
    }
}
