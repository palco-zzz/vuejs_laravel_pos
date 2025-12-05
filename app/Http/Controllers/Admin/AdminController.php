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
            'payment_method' => ['nullable', 'string', 'in:cash,transfer,qris'],
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
}
