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
        $query = Order::with(['items.menu', 'branch', 'editor'])
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
                'edited_by' => $order->edited_by,
                'edited_at' => $order->edited_at ? $order->edited_at->format('d/m/Y H:i') : null,
                'edit_reason' => $order->edit_reason,
                'editor_name' => $order->editor->name ?? null,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
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
            'menus' => Menu::with('category')
                ->where('stok', '>', 0)
                ->orderBy('nama')
                ->get()
                ->map(function ($menu) {
                    return [
                        'id' => $menu->id,
                        'nama' => $menu->nama,
                        'harga' => (float) $menu->harga,
                        'category_name' => $menu->category->nama ?? 'Uncategorized',
                    ];
                }),
        ]);
    }

    public function updateItems(Request $request, Order $order)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.menu_id' => ['nullable', 'integer', 'exists:menus,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'edit_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::beginTransaction();

            $newSubtotal = 0;

            // Update each item and recalculate subtotals
            foreach ($validated['items'] as $itemData) {
                $orderItem = OrderItem::findOrFail($itemData['order_item_id']);
                
                // Ensure this item belongs to this order
                if ($orderItem->order_id !== $order->id) {
                    throw new \Exception('Item does not belong to this order');
                }
                
                $newPrice = $orderItem->price;
                $newItemName = $orderItem->item_name;
                $newMenuId = $orderItem->menu_id;
                
                // If menu_id is provided and different from current, fetch fresh price and name
                if (isset($itemData['menu_id']) && $itemData['menu_id'] !== $orderItem->menu_id) {
                    $menu = Menu::findOrFail($itemData['menu_id']);
                    $newPrice = $menu->harga;
                    $newItemName = $menu->nama;
                    $newMenuId = $menu->id;
                }
                
                // Calculate new subtotal for this item
                $newItemSubtotal = $newPrice * $itemData['quantity'];
                
                // Update the order item with potentially new menu, price, name, quantity
                $orderItem->update([
                    'menu_id' => $newMenuId,
                    'item_name' => $newItemName,
                    'price' => $newPrice,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $newItemSubtotal,
                ]);
                
                $newSubtotal += $newItemSubtotal;
            }

            // Recalculate order totals (assuming 10% tax as per typical setup)
            $taxRate = 0.10;
            $newTax = $newSubtotal * $taxRate;
            $newTotal = $newSubtotal + $newTax;

            // Update order with new totals and audit info
            $order->update([
                'subtotal' => $newSubtotal,
                'tax' => $newTax,
                'total' => $newTotal,
                'edited_by' => Auth::id(),
                'edited_at' => now(),
                'edit_reason' => $validated['edit_reason'],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Item order berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui item: ' . $e->getMessage());
        }
    }
}
