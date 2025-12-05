<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index()
    {
        $menus = Menu::with('category')
            ->where('stok', '>', 0)
            ->orderBy('nama')
            ->get();

        $categories = Category::orderBy('nama')->get();

        return Inertia::render('admin/IndexPos', [
            'menus' => $menus,
            'categories' => $categories,
        ]);
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.is_custom' => ['boolean'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,bca_va,bri_va,gopay,ovo,transfer,qris'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'change_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => Auth::id(),
                'branch_id' => Auth::user()->branch_id ?? null,
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'],
                'total' => $validated['total'],
                'status' => 'completed',
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'cash_amount' => $validated['cash_amount'] ?? null,
                'change_amount' => $validated['change_amount'] ?? null,
            ]);

            // Create order items and update stock
            foreach ($validated['items'] as $item) {
                $isCustom = $item['is_custom'] ?? false;

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $isCustom ? null : ($item['id'] ?? null),
                    'item_name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['qty'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'is_custom' => $isCustom,
                ]);

                // Decrease stock for non-custom items
                if (!$isCustom && isset($item['id'])) {
                    Menu::where('id', $item['id'])->decrement('stok', $item['qty']);
                }
            }

            DB::commit();

            // Return order data for receipt printing
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil disimpan',
                'order' => $order->load('items'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history()
    {
        $user = Auth::user();
        
        // Build query based on user role
        $query = Order::with(['items.menu', 'branch'])
            ->orderBy('created_at', 'desc');
        
        // Cashiers see only their own transactions
        if ($user->role === 'cashier') {
            $query->where('user_id', $user->id);
        }
        // Admins with branch see their branch transactions
        elseif ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        // Super admins (branch_id = null) see all transactions
        
        $transactions = $query->paginate(10)->through(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'date' => $order->created_at->format('d/m/Y'),
                'time' => $order->created_at->format('H:i'),
                'total' => (float) $order->total,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'branch_name' => $order->branch->nama ?? '-',
                'branch_address' => $order->branch->address ?? '',
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

        return Inertia::render('admin/History/Index', [
            'transactions' => $transactions,
        ]);
    }
}
