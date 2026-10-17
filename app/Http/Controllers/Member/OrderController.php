<?php

namespace App\Http\Controllers\Member;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Checkout\InvoiceService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,paid,expired,cancelled,failed,refunded']]);
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('member.orders.index', compact('orders', 'filters'));
    }

    public function invoice(Request $request, Order $order, InvoiceService $invoices): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === OrderStatus::Paid, 404);

        $invoice = $invoices->forPaidOrder($order);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', storage_path('framework/cache'));
        $options->set('fontCache', storage_path('framework/cache'));

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('member.orders.invoice', compact('invoice'))->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
