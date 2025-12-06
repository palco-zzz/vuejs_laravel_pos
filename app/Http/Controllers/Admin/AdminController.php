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
        
        // Fetch all branches for admin branch selection
        $branches = \App\Models\Branch::select('id', 'nama')->orderBy('nama')->get();

        return Inertia::render('admin/IndexPos', [
            'menus' => $menus,
            'categories' => $categories,
            'branches' => $branches,
        ]);
    }

    public function storeOrder(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }
            
            // ========================================
            // STEP 1: Determine Target Branch ID FIRST
            // ========================================
            // Priority: If Admin, use Request Input. If Cashier, use User Attribute.
            $targetBranchId = $user->role === 'admin' 
                ? $request->input('branch_id') 
                : $user->branch_id;
            
            // Safety Check - Branch ID is REQUIRED
            if (!$targetBranchId) {
                $errorMessage = $user->role === 'admin' 
                    ? 'Admin harus memilih cabang terlebih dahulu' 
                    : 'Kasir belum ditugaskan ke cabang manapun';
                    
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }
            
            // Log for debugging
            \Log::info('POS Checkout', [
                'user_id' => $user->id, 
                'role' => $user->role, 
                'target_branch_id' => $targetBranchId,
                'branch_id_from_request' => $request->input('branch_id'),
                'branch_id_from_user' => $user->branch_id,
            ]);
            
            // ========================================
            // STEP 2: Validate Request Data
            // ========================================
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
                'branch_id' => ['nullable', 'integer', 'exists:branch,id'],
            ]);

            // ========================================
            // STEP 3: Create Order with Transaction
            // ========================================
            DB::beginTransaction();

            // Create order - USE $targetBranchId here!
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'branch_id' => $targetBranchId,  // <-- FIXED: Uses determined branch
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

            \Log::info('POS Checkout Success', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'branch_id' => $targetBranchId,
            ]);

            // Return order data for receipt printing
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil disimpan',
                'order' => $order->load('items'),
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('POS Checkout Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history()
    {
        $user = Auth::user();
        
        // Build query - Include soft deleted (voided) transactions
        $query = Order::with(['items.menu', 'branch', 'editor', 'user', 'deleter'])
            ->withTrashed()  // Show voided transactions too
            ->orderBy('created_at', 'desc');
        
        // Cashiers see ALL transactions FOR THEIR BRANCH (not just their own)
        if ($user->role === 'cashier') {
            if (!$user->branch_id) {
                // Safety check: cashier without branch sees nothing
                $query->whereRaw('1 = 0');
            } else {
                // Show all transactions for the cashier's branch
                $query->where('branch_id', $user->branch_id);
                
                // Optional: Show only today's transactions by default
                // Comment this line if you want to show all historical transactions
                $query->whereDate('created_at', \Carbon\Carbon::today());
            }
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
                // Creator info for "BANTUAN ADMIN" badge
                'creator_name' => $order->user->name ?? null,
                'creator_role' => $order->user->role ?? null,
                // Void info for soft deletes
                'deleted_at' => $order->deleted_at ? $order->deleted_at->format('d/m/Y H:i') : null,
                'deleted_by' => $order->deleted_by,
                'delete_reason' => $order->delete_reason,
                'deleter_name' => $order->deleter->name ?? null,
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
            'items.*.id' => ['nullable', 'integer', 'exists:order_items,id'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'edit_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::beginTransaction();

            // Get current order item IDs
            $currentItemIds = $order->items->pluck('id')->toArray();
            
            // Get request item IDs (only non-null)
            $requestItemIds = collect($validated['items'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // STEP 1: Delete items that are not in the request (DELETION)
            $itemsToDelete = array_diff($currentItemIds, $requestItemIds);
            if (!empty($itemsToDelete)) {
                OrderItem::whereIn('id', $itemsToDelete)
                    ->where('order_id', $order->id)
                    ->delete();
            }

            $newSubtotal = 0;

            // STEP 2: Update existing items or create new ones (UPDATE & CREATE)
            foreach ($validated['items'] as $itemData) {
                $menu = Menu::findOrFail($itemData['menu_id']);
                
                // Always fetch fresh price from database (security)
                $freshPrice = $menu->harga;
                $itemName = $menu->nama;
                $quantity = $itemData['quantity'];
                $subtotal = $freshPrice * $quantity;

                if (isset($itemData['id']) && $itemData['id']) {
                    // UPDATE existing item
                    $orderItem = OrderItem::where('id', $itemData['id'])
                        ->where('order_id', $order->id)
                        ->firstOrFail();
                    
                    $orderItem->update([
                        'menu_id' => $menu->id,
                        'item_name' => $itemName,
                        'price' => $freshPrice,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'is_custom' => false,
                    ]);
                } else {
                    // CREATE new item
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_id' => $menu->id,
                        'item_name' => $itemName,
                        'price' => $freshPrice,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'is_custom' => false,
                    ]);
                }

                $newSubtotal += $subtotal;
            }

            // STEP 3: Recalculate order totals
            $taxRate = 0.10;
            $newTax = $newSubtotal * $taxRate;
            $newTotal = $newSubtotal + $newTax;

            // STEP 4: Update order with new totals and audit info
            $order->update([
                'subtotal' => $newSubtotal,
                'tax' => $newTax,
                'total' => $newTotal,
                'edited_by' => Auth::id(),
                'edited_at' => now(),
                'edit_reason' => $validated['edit_reason'],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Order berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }

    /**
     * Void (Soft Delete) a transaction with audit trail
     */
    public function voidTransaction(Request $request, Order $order)
    {
        // Only allow non-deleted orders to be voided
        if ($order->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah dibatalkan sebelumnya',
            ], 422);
        }

        $validated = $request->validate([
            'delete_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::beginTransaction();

            // Stock reversal: Return items back to inventory
            foreach ($order->items as $item) {
                if (!$item->is_custom && $item->menu_id) {
                    Menu::where('id', $item->menu_id)
                        ->increment('stok', $item->quantity);
                }
            }

            // Set audit trail before deleting
            $order->deleted_by = Auth::id();
            $order->delete_reason = $validated['delete_reason'];
            $order->save();

            // Soft delete the order
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Void Transaction Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
