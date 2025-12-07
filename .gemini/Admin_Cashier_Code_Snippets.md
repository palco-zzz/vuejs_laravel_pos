# Admin-Cashier Fixes - Code Snippets

## Part 1: TransactionController - Redirect Fix

### File: `app/Http/Controllers/TransactionController.php`

**Success Return (Line ~150):**
```php
// OLD:
return response()->json([
    'success' => true,
    'message' => 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.',
]);

// NEW:
return redirect()->back()->with('success', 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.');
```

**Error Returns (Lines 82-106):**
```php
// OLD:
return response()->json([
    'success' => false,
    'message' => 'Hanya transaksi dengan status success yang dapat dibatalkan.',
], 422);

// NEW:
return redirect()->back()->with('error', 'Hanya transaksi dengan status success yang dapat dibatalkan.');
```

---

## Part 2: Financial Report - Remove Actions

### File: `resources/js/pages/reports/Transactions.vue`

**Removed Action Column Header:**
```vue
<!-- REMOVED: Lines 360-362 -->
<th class="px-6 py-4 text-center ...">
    Aksi
</th>
```

**Removed Action Buttons from Table Body:**
```vue
<!-- REMOVED: Lines 410-429 - Entire <td> with buttons -->
<td class="px-6 py-4 text-center">
    <div class="flex items-center justify-center gap-2">
        <button @click="openDetailModal(transaction)">...</button>
        <button @click="openEditModal(transaction)">...</button>
        <button @click="openVoidModal(transaction)">...</button>
    </div>
</td>
```

**Updated Imports:**
```vue
// OLD:
import { Calendar, Download, Filter, X, Eye, Pencil, Trash2, AlertCircle } from 'lucide-vue-next';

// NEW:
import { Calendar, Download, Filter, X, Eye, AlertCircle } from 'lucide-vue-next';
```

---

## Part 3A: Backend - AdminController Query Fix

### File: `app/Http/Controllers/Admin/AdminController.php`

**Query Changes (history method):**
```php
// OLD:
$query = Order::with(['items.menu', 'branch', 'editor'])
    ->orderBy('created_at', 'desc');

// Cashiers see only their own transactions
if ($user->role === 'cashier') {
    $query->where('user_id', $user->id);
}

// NEW:
$query = Order::with(['items.menu', 'branch', 'user', 'editor', 'deleter'])
    ->orderBy('created_at', 'desc');

// Cashiers see all transactions from their branch (including admin's)
if ($user->role === 'cashier') {
    $query->where('branch_id', $user->branch_id);
}
```

**Added to Response Array:**
```php
$transactions = $query->paginate(10)->through(function ($order) {
    return [
        // ... existing fields ...
        
        // User/Creator info (for "BANTUAN PUSAT" badge)
        'user' => [
            'id' => $order->user->id,
            'name' => $order->user->name,
            'role' => $order->user->role,
        ],
        
        // Deleter info (for void tracking)
        'deleted_at' => $order->deleted_at ? $order->deleted_at->format('d/m/Y H:i') : null,
        'deleted_by' => $order->deleted_by,
        'delete_reason' => $order->delete_reason,
        'deleter_name' => $order->deleter->name ?? null,
    ];
});
```

---

## Part 3B: Frontend - History Vue Component

### File: `resources/js/pages/admin/History/Index.vue`

**Updated Interface (Lines 20-42):**
```typescript
interface Transaction {
    id: number;
    order_number: string;
    date: string;
    time: string;
    total: number;
    payment_method: string;
    status: string;
    branch_name: string;
    branch_address: string;
    
    // User/Creator info (for "BANTUAN PUSAT" badge)
    user: {
        id: number;
        name: string;
        role: string;
    };
    
    // Editor info (for "DIEDIT" badge)
    edited_by?: number | null;
    edited_at?: string | null;
    edit_reason?: string | null;
    editor_name?: string | null;
    
    // Deleter info (for void tracking)
    deleted_at?: string | null;
    deleted_by?: number | null;
    delete_reason?: string | null;
    deleter_name?: string | null;
    
    items: TransactionItem[];
}
```

**Added Badges in Table (Lines ~544-559):**
```vue
<td class="px-6 py-4 text-center">
    <div class="flex items-center justify-center gap-2">
        <!-- Existing Status Badge -->
        <span :class="getStatusBadgeClass(transaction.status)"
              class="text-xs px-2.5 py-1 rounded-full">
            {{ getStatusLabel(transaction.status) }}
        </span>
        
        <!-- NEW: "BANTUAN PUSAT" badge if created by admin -->
        <span v-if="transaction.user.role === 'admin'"
              title="Transaksi ini dibuat oleh Admin"
              class="text-xs px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 cursor-help">
            BANTUAN PUSAT
        </span>
        
        <!-- Existing "DIEDIT" badge (now with complete data) -->
        <span v-if="transaction.edited_at"
              :title="`Diedit oleh ${transaction.editor_name} pada ${transaction.edited_at}\nAlasan: ${transaction.edit_reason}`"
              class="text-xs px-2.5 py-1 rounded-full bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 cursor-help">
            DIEDIT
        </span>
    </div>
</td>
```

---

## Badge Styling Reference

```vue
<!-- Blue Badge: BANTUAN PUSAT -->
<span class="bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400">
    BANTUAN PUSAT
</span>

<!-- Yellow Badge: DIEDIT -->
<span class="bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400">
    DIEDIT
</span>
```

---

## Order Model Reference

The Order model already has the necessary relationships:

```php
// app/Models/Order.php

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function editor(): BelongsTo
{
    return $this->belongsTo(User::class, 'edited_by');
}

public function deleter(): BelongsTo
{
    return $this->belongsTo(User::class, 'deleted_by');
}
```

---

## Testing URLs

- **Financial Report (Read-Only):** `/reports/transactions`
- **Transaction History (Full Access):** `/pos/history`
- **Void Transaction API:** `DELETE /pos/order/{id}/void`
