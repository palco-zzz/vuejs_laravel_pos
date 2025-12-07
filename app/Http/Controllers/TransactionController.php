<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Hard delete a pending order (trash)
     * Only for orders with status 'pending'
     */
    public function destroyPending(Order $order)
    {
        $user = Auth::user();

        // Verify the order status is pending
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya order dengan status pending yang dapat dihapus permanen.',
            ], 422);
        }

        // For cashiers, verify branch access
        if ($user->role === 'cashier' && $order->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus order ini.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $orderNumber = $order->order_number;

            // Hard delete (permanent deletion)
            $order->forceDelete();

            DB::commit();

            Log::info('Order Hard Deleted', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order pending berhasil dihapus permanen.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Hard Delete Order Failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Void/Cancel a successful transaction
     * Only for orders with status 'success' and created today
     * Performs soft delete and restores stock
     */
    public function voidTransaction(Request $request, Order $order)
    {
        $user = Auth::user();

        // Validate delete_reason
        $validated = $request->validate([
            'delete_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        // Verify the order status is 'success'
        if ($order->status !== 'success') {
            return redirect()->back()->with('error', 'Hanya transaksi dengan status success yang dapat dibatalkan.');
        }

        // For cashiers, verify branch access
        if ($user->role === 'cashier') {
            if ($order->branch_id !== $user->branch_id) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk membatalkan transaksi ini.');
            }

            // Verify the order is from today
            if (!$order->created_at->isToday()) {
                return redirect()->back()->with('error', 'Kasir hanya dapat membatalkan transaksi dari hari ini.');
            }
        }

        try {
            DB::beginTransaction();

            // Restore stock for all items in the order
            foreach ($order->items as $item) {
                // Only restore stock for non-custom items
                if (!$item->is_custom && $item->menu_id) {
                    Menu::where('id', $item->menu_id)
                        ->increment('stok', $item->quantity);
                }
            }

            // Update order status to 'cancelled' and perform soft delete
            $order->update([
                'status' => 'cancelled',
                'deleted_by' => $user->id,
                'delete_reason' => $validated['delete_reason'],
            ]);

            // Soft delete the order
            $order->delete();

            DB::commit();

            Log::info('Transaction Voided', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'voided_by' => $user->id,
                'user_role' => $user->role,
                'reason' => $validated['delete_reason'],
            ]);

            return redirect()->back()->with('success', 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Void Transaction Failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }
}
