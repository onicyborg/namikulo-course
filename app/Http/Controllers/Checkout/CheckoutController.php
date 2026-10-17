<?php

namespace App\Http\Controllers\Checkout;

use App\Enums\CourseStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Checkout\PaymentInstructionEmailService;
use App\Services\Checkout\OrderService;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, Course $course, EnrollmentService $enrollments): View|RedirectResponse
    {
        abort_unless($course->status === CourseStatus::Published, 404);
        if ($enrollments->activeFor($request->user(), $course)) {
            return redirect()->route('member.dashboard')->with('success', 'Akses course ini masih aktif.');
        }
        $pending = $request->user()->orders()->where('course_id', $course->id)
            ->where('status', 'pending')->latest()->first();

        return view('checkout.show', [
            'course' => $course, 'pending' => $pending,
            'channels' => config('midtrans.enabled_channels', []),
        ]);
    }

    public function store(Request $request, Course $course, OrderService $orders, PaymentInstructionEmailService $email): RedirectResponse
    {
        $data = $request->validate(['channel' => ['required', 'string', 'in:'.implode(',', config('midtrans.enabled_channels', []))]]);
        $order = $orders->start($request->user(), $course, $data['channel']);
        $payment = $order->payments()->latest()->first();
        $emailNotice = null;
        if ($payment?->status === PaymentStatus::Pending) {
            try {
                $email->sendOnce($payment->id);
            } catch (\Throwable) {
                $emailNotice = 'Email instruksi belum berhasil dikirim. Detail pembayaran tetap tersedia di halaman ini.';
            }
            if (! $payment->fresh()?->instruction_email_sent_at) {
                $emailNotice ??= 'Email instruksi belum tercatat terkirim. Detail pembayaran tetap tersedia di halaman ini.';
            }
        }

        $response = redirect()->route('orders.pay', $order);
        return $emailNotice ? $response->with('error', $emailNotice) : $response;
    }
}
