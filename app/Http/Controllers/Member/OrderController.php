<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\JsonResponse;
use App\Services\ServerTable;
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
        $filters = $request->validate(['status' => ['nullable', 'in:pending,paid,expired,cancelled,failed,refunded'],
            'q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100']]);
        $orders = Order::query()->where('user_id', $request->user()->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $orders->where(fn ($q) => $q->whereRaw('LOWER(course_title) LIKE ?', [$term])->orWhereRaw('LOWER(order_number) LIKE ?', [$term]));
        }
        if ($asData) return $table->response($request, $orders,
            ['order', 'created', 'amount', 'status', 'actions'], 'member.orders._cell', 'order',
            [0 => 'course_title', 1 => 'created_at', 2 => 'amount', 3 => 'status'], 'created_at', 'desc',
            Order::query()->where('user_id', $request->user()->id)->count());
        $orders = $orders->latest('created_at')->orderBy('id')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();
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
