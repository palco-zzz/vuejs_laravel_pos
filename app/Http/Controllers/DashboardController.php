<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Check if user is a cashier with a specific branch
        $isCashier = $user->role === 'cashier';
        $branchId = $user->branch_id;

        // 1. Total Income Today
        $totalIncomeQuery = Order::whereDate('created_at', $today);
        if ($isCashier && $branchId) {
            $totalIncomeQuery->where('branch_id', $branchId);
        }
        $totalIncome = $totalIncomeQuery->sum('total');

        // 2. Total Transactions Today
        $totalTransactionsQuery = Order::whereDate('created_at', $today);
        if ($isCashier && $branchId) {
            $totalTransactionsQuery->where('branch_id', $branchId);
        }
        $totalTransactions = $totalTransactionsQuery->count();

        // 3. Active Branches (count of all branches) - Admins only see all, cashiers see only their branch
        $activeBranches = $isCashier && $branchId ? 1 : Branch::count();

        // 4. Top Selling Menus - Query based on branch
        $topSellingQuery = OrderItem::select('menu_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereNotNull('menu_id')
            ->whereHas('order', function ($query) use ($isCashier, $branchId, $today) {
                $query->whereDate('created_at', $today);
                if ($isCashier && $branchId) {
                    $query->where('branch_id', $branchId);
                }
            });
        
        $topSellingMenus = $topSellingQuery
            ->groupBy('menu_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->with(['menu' => function ($query) {
                $query->select('id', 'nama', 'icon');
            }])
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->menu ? $item->menu->nama : 'Unknown Item',
                    'total_sold' => (int) $item->total_sold,
                    'icon' => $item->menu ? $item->menu->icon : null,
                ];
            });

        // 5. Branch Performance - Filter for cashiers
        $branchQuery = Branch::query();
        if ($isCashier && $branchId) {
            $branchQuery->where('id', $branchId);
        }
        
        $branchPerformance = $branchQuery->withCount(['orders as transaction_count' => function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            }])
            ->withSum(['orders as total_income' => function ($query) use ($today) {
                $query->whereDate('created_at', $today);
            }], 'total')
            ->with(['orders' => function ($query) {
                $query->latest()->take(1);
            }])
            ->get()
            ->map(function ($branch) {
                $latestOrder = $branch->orders->first();
                return [
                    'id' => $branch->id,
                    'name' => $branch->nama,
                    'address' => $branch->address ?? '-',
                    'transaction_count' => (int) $branch->transaction_count,
                    'total_income' => (float) ($branch->total_income ?? 0),
                    'latest_activity' => $latestOrder ? $latestOrder->created_at->diffForHumans() : 'Belum ada aktivitas',
                    'status' => 'active',
                ];
            });

        // 6. Latest Transactions for "Pesanan Masuk" widget - Scoped by branch for cashiers
        $latestTransactionsQuery = Order::with(['branch', 'items.menu']);
        if ($isCashier && $branchId) {
            $latestTransactionsQuery->where('branch_id', $branchId);
        }
        
        $latestTransactions = $latestTransactionsQuery
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                $itemNames = $order->items->map(fn($item) => 
                    ($item->quantity > 1 ? $item->quantity . 'x ' : '') . ($item->menu->nama ?? 'Item')
                )->join(', ');
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'items' => $itemNames ?: 'Custom Order',
                    'branch_name' => $order->branch->nama ?? 'Unknown',
                    'total' => (float) $order->total,
                    'time_ago' => $order->created_at->diffForHumans(),
                    'is_new' => $order->created_at->gt(now()->subMinutes(5)),
                ];
            });

        // 7. Weekly Sales Chart Data (Last 7 days: Monday to Sunday) - Scoped by branch for cashiers
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday
        $endOfWeek = Carbon::now()->endOfWeek(); // Sunday
        
        $weeklySalesQuery = Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
        if ($isCashier && $branchId) {
            $weeklySalesQuery->where('branch_id', $branchId);
        }
        
        $weeklySales = $weeklySalesQuery->groupBy('date')->pluck('total', 'date');

        // Fill in all 7 days (Monday to Sunday) with 0 if no sales
        $chartLabels = [];
        $chartData = [];
        
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dateKey = $currentDay->format('Y-m-d');
            
            $chartLabels[] = $currentDay->locale('id')->isoFormat('ddd'); // Mon, Tue, etc in Indonesian
            $chartData[] = (float) ($weeklySales[$dateKey] ?? 0);
        }

        return Inertia::render('Dashboard', [
            'totalIncome' => (float) $totalIncome,
            'totalTransactions' => $totalTransactions,
            'activeBranches' => $activeBranches,
            'topSellingMenus' => $topSellingMenus,
            'branchPerformance' => $branchPerformance,
            'latestTransactions' => $latestTransactions,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ]);
    }
}
