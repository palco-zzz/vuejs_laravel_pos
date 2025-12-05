<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function transactions(Request $request)
    {
        // Get filter parameters
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $branchId = $request->input('branch_id');
        $paymentMethod = $request->input('payment_method');

        // Build query
        $query = Order::with(['branch', 'user', 'items.menu'])
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        // Get transactions
        $transactions = $query->latest()->paginate(20)->through(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'date_time' => $order->created_at->format('d/m/Y H:i'),
                'branch_name' => $order->branch->nama ?? '-',
                'cashier_name' => $order->user->name ?? '-',
                'total' => (float) $order->total,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->item_name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => (float) $item->subtotal,
                        'is_custom' => $item->is_custom,
                    ];
                }),
            ];
        });

        // Calculate summary
        $summary = [
            'total_revenue' => (float) Order::whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($paymentMethod, fn($q) => $q->where('payment_method', $paymentMethod))
                ->sum('total'),
            'total_count' => Order::whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($paymentMethod, fn($q) => $q->where('payment_method', $paymentMethod))
                ->count(),
        ];

        // Get branches for filter
        $branches = Branch::select('id', 'nama')->get();

        // Payment methods
        $paymentMethods = [
            ['value' => 'cash', 'label' => 'Tunai'],
            ['value' => 'bca_va', 'label' => 'BCA Virtual Account'],
            ['value' => 'bri_va', 'label' => 'BRI Virtual Account'],
            ['value' => 'gopay', 'label' => 'GoPay'],
            ['value' => 'ovo', 'label' => 'OVO'],
            ['value' => 'transfer', 'label' => 'Transfer'],
            ['value' => 'qris', 'label' => 'QRIS'],
        ];

        return Inertia::render('reports/Transactions', [
            'transactions' => $transactions,
            'summary' => $summary,
            'branches' => $branches,
            'paymentMethods' => $paymentMethods,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id' => $branchId,
                'payment_method' => $paymentMethod,
            ],
        ]);
    }
}
