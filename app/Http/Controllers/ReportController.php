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

        // Build query - Include voided transactions
        $query = Order::withTrashed()
            ->with(['branch', 'user', 'items.menu', 'editor', 'deleter'])
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
                'creator_role' => $order->user->role ?? null,  // For "BANTUAN ADMIN" badge
                // Void/Delete info for soft deletes
                'deleted_at' => $order->deleted_at ? $order->deleted_at->format('d/m/Y H:i') : null,
                'deleted_by' => $order->deleted_by,
                'delete_reason' => $order->delete_reason,
                'deleter_name' => $order->deleter->name ?? null,
                // Edit info for tracking changes
                'edited_at' => $order->edited_at ? $order->edited_at->format('d/m/Y H:i') : null,
                'edited_by' => $order->edited_by,
                'edit_reason' => $order->edit_reason,
                'edited_by_name' => $order->editor->name ?? null,
                // Transaction details
                'total' => (float) $order->total,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'notes' => $order->notes,
                'items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->item_name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => (float) $item->subtotal,
                        'is_custom' => $item->is_custom,
                        'note' => $item->note,
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

    public function menuAnalysis(Request $request)
    {
        // Get filter parameters
        $dateRange = $request->input('date_range', 'this_month');
        $branchId = $request->input('branch_id');
        
        // Calculate date range
        [$startDate, $endDate] = $this->getDateRange($dateRange);
        
        // Query 1: Top Performers (Menu Sales)
        $topPerformersQuery = \App\Models\OrderItem::with('menu.category')
            ->select(
                'menu_id',
                \DB::raw('SUM(quantity) as total_sold'),
                \DB::raw('SUM(subtotal) as total_revenue')
            )
            ->whereNotNull('menu_id')
            ->whereHas('order', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->groupBy('menu_id')
            ->orderByDesc('total_sold');
        
        $topPerformers = $topPerformersQuery->get()->map(function ($item) {
            return [
                'menu_id' => $item->menu_id,
                'menu_name' => $item->menu->nama ?? 'Unknown',
                'category_name' => $item->menu->category->nama ?? 'Uncategorized',
                'price' => (float) ($item->menu->harga ?? 0),
                'icon' => $item->menu->icon ?? '🍽️',
                'total_sold' => (int) $item->total_sold,
                'total_revenue' => (float) $item->total_revenue,
            ];
        });
        
        // Query 2: Category Breakdown
        $categoryBreakdown = \App\Models\OrderItem::with('menu.category')
            ->select(
                \DB::raw('COALESCE(menus.category_id, 0) as category_id'),
                \DB::raw('SUM(order_items.quantity) as total_sold'),
                \DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->leftJoin('menus', 'order_items.menu_id', '=', 'menus.id')
            ->whereHas('order', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                $category = \App\Models\Category::find($item->category_id);
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $category->nama ?? 'Custom Items',
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => (float) $item->total_revenue,
                    'percentage' => 0, // Will calculate after
                ];
            });
        
        // Calculate percentages
        $totalSold = $categoryBreakdown->sum('total_sold');
        $categoryBreakdown = $categoryBreakdown->map(function ($item) use ($totalSold) {
            $item['percentage'] = $totalSold > 0 ? round(($item['total_sold'] / $totalSold) * 100, 1) : 0;
            return $item;
        });
        
        // Query 3: Zero Sales (Dead Stock)
        $soldMenuIds = $topPerformers->pluck('menu_id')->toArray();
        $zeroSales = \App\Models\Menu::with('category')
            ->whereNotIn('id', $soldMenuIds)
            ->get()
            ->map(function ($menu) {
                return [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->nama,
                    'category_name' => $menu->category->nama ?? 'Uncategorized',
                    'price' => (float) $menu->harga,
                    'stock' => $menu->stok,
                ];
            });
        
        // Summary Cards
        $summary = [
            'total_items_sold' => (int) $topPerformers->sum('total_sold'),
            'total_revenue' => (float) $topPerformers->sum('total_revenue'),
            'best_selling_item' => $topPerformers->first() ? [
                'name' => $topPerformers->first()['menu_name'],
                'sold' => $topPerformers->first()['total_sold'],
            ] : null,
            'highest_revenue_item' => $topPerformers->sortByDesc('total_revenue')->first() ? [
                'name' => $topPerformers->sortByDesc('total_revenue')->first()['menu_name'],
                'revenue' => $topPerformers->sortByDesc('total_revenue')->first()['total_revenue'],
            ] : null,
            'zero_sales_count' => $zeroSales->count(),
        ];
        
        // Get branches for filter
        $branches = Branch::select('id', 'nama')->get();
        
        return Inertia::render('reports/MenuAnalysis', [
            'topPerformers' => $topPerformers,
            'categoryBreakdown' => $categoryBreakdown->values(),
            'zeroSales' => $zeroSales,
            'summary' => $summary,
            'branches' => $branches,
            'filters' => [
                'date_range' => $dateRange,
                'branch_id' => $branchId,
            ],
        ]);
    }
    
    public function updateTransaction(Request $request, Order $order)
    {
        // Validate the request
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bca_va,bri_va,gopay,ovo,transfer,qris',
            'status' => 'required|in:completed,pending,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update the order
        $order->update([
            'payment_method' => $validated['payment_method'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $order->notes,
        ]);

        return redirect()->back()->with('success', 'Transaksi berhasil diperbarui');
    }

    /**
     * Top Menus for Cashier - Shows branch-specific top selling menus
     */
    public function topMenusCashier(Request $request)
    {
        $user = auth()->user();
        
        // If user has no branch, show error
        if (!$user->branch_id) {
            return redirect()->route('dashboard')->with('error', 'Anda belum ditugaskan ke cabang manapun.');
        }
        
        // Get date filters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        
        // Get branch info
        $branch = Branch::find($user->branch_id);
        
        // Query top selling items for this branch
        $topMenus = \App\Models\OrderItem::with('menu.category')
            ->select(
                'menu_id',
                \DB::raw('SUM(quantity) as total_sold'),
                \DB::raw('SUM(subtotal) as total_revenue')
            )
            ->whereNotNull('menu_id')
            ->whereHas('order', function ($q) use ($user, $startDate, $endDate) {
                $q->where('branch_id', $user->branch_id)
                  ->where('status', 'success')
                  ->whereBetween('created_at', [
                      Carbon::parse($startDate)->startOfDay(),
                      Carbon::parse($endDate)->endOfDay()
                  ]);
            })
            ->groupBy('menu_id')
            ->orderByDesc('total_sold')
            ->limit(20)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'rank' => $index + 1,
                    'menu_id' => $item->menu_id,
                    'menu_name' => $item->menu->nama ?? 'Unknown',
                    'category_name' => $item->menu->category->nama ?? 'Uncategorized',
                    'icon' => $item->menu->icon ?? '🍽️',
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            });
        
        // Calculate summary
        $summary = [
            'total_items_sold' => $topMenus->sum('total_sold'),
            'total_revenue' => $topMenus->sum('total_revenue'),
            'unique_menus' => $topMenus->count(),
        ];
        
        return Inertia::render('pos/TopMenus', [
            'topMenus' => $topMenus,
            'summary' => $summary,
            'branchName' => $branch->nama ?? 'Cabang',
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    private function getDateRange($dateRange)
    {
        return match($dateRange) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'last_week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}
