# Role-Based Access Control (RBAC) Implementation Guide

## Overview
This document outlines the complete implementation of Role-Based Access Control (RBAC) for the POS application, restricting access based on two roles: **Admin** and **Cashier**.

---

## 1. Database & Middleware

### ✅ Users Table
The `users` table already has a `role` column with values: `'admin'` or `'cashier'`.

### ✅ CheckRole Middleware Created
**File**: `app/Http/Middleware/CheckRole.php`

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Check if user has one of the allowed roles
        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
```

### ✅ Middleware Registered
**File**: `bootstrap/app.php`

```php
$middleware->alias([
    'role' => \App\Http\Middleware\CheckRole::class,
]);
```

**Usage in Routes**:
```php
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    // Admin-only routes
});
```

---

## 2. Shared Props (HandleInertiaRequests)

### ✅ User Role & Permissions Shared to Vue
**File**: `app/Http/Middleware/HandleInertiaRequests.php`

**Added to share() method**:
```php
'auth' => [
    'user' => $user,
    'role' => $user?->role,
    'permissions' => $this->getUserPermissions($user),
]
```

**Permission Mapping**:

| Permission | Admin | Cashier |
|------------|-------|---------|
| `view_all_branches` | ✅ | ❌ |
| `manage_branches` | ✅ | ❌ |
| `manage_menu` | ✅ | ❌ |
| `manage_employees` | ✅ | ❌ |
| `view_reports` | ✅ | ❌ |
| `manage_pos` | ✅ | ✅ |

---

## 3. Sidebar Logic (Vue)

### ✅ Updated TypeScript Types
**File**: `resources/js/types/index.d.ts`

```typescript
export interface Auth {
    user: User;
    role: 'admin' | 'cashier' | null;
    permissions: {
        view_all_branches?: boolean;
        manage_branches?: boolean;
        manage_menu?: boolean;
        manage_employees?: boolean;
        view_reports?: boolean;
        manage_pos?: boolean;
    };
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    permission?: string | null; // NEW
}
```

### ✅ Dynamic Sidebar Filtering
**File**: `resources/js/components/AppSidebar.vue`

**Menu Items with Permissions**:
```typescript
const allNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/', icon: LayoutGrid, permission: null }, // Everyone
    { title: 'Penjualan (POS)', href: '/pos', icon: ShoppingBag, permission: 'manage_pos' },
    { title: 'Manajemen Cabang', href: '/branch', icon: Store, permission: 'manage_branches' }, // Admin only
    { title: 'Manajemen Menu', href: '/menu', icon: Package, permission: 'manage_menu' }, // Admin only
    { title: 'Stok Bahan', href: '/stok', icon: Package, permission: 'manage_menu' }, // Admin only
    { title: 'Karyawan', href: '/karyawan', icon: User, permission: 'manage_employees' }, // Admin only
    { title: 'Laporan', href: '/reports/transactions', icon: BarChart3, permission: 'view_reports' }, // Admin only
];
```

**Filtering Logic**:
```typescript
const mainNavItems = computed(() => {
    return allNavItems.filter(item => {
        if (!item.permission) return true; // Show to everyone
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });
});
```

**Result**:
- **Cashier sees**: Dashboard, Penjualan (POS)
- **Admin sees**: All menu items

---

## 4. Data Scoping (DashboardController)

### ✅ Branch-Scoped Dashboard Data
**File**: `app/Http/Controllers/DashboardController.php`

**Key Changes**:

1. **Detect User Role**:
```php
$user = Auth::user();
$isCashier = $user->role === 'cashier';
$branchId = $user->branch_id;
```

2. **Scope Total Income & Transactions**:
```php
$totalIncomeQuery = Order::whereDate('created_at', $today);
if ($isCashier && $branchId) {
    $totalIncomeQuery->where('branch_id', $branchId);
}
$totalIncome = $totalIncomeQuery->sum('total');
```

3. **Scope Active Branches**:
```php
$activeBranches = $isCashier && $branchId ? 1 : Branch::count();
```

4. **Scope Top Selling Menus** (Today's sales only, scoped by branch):
```php
$topSellingQuery = OrderItem::select('menu_id', DB::raw('SUM(quantity) as total_sold'))
    ->whereNotNull('menu_id')
    ->whereHas('order', function ($query) use ($isCashier, $branchId, $today) {
        $query->whereDate('created_at', $today);
        if ($isCashier && $branchId) {
            $query->where('branch_id', $branchId);
        }
    });
```

5. **Scope Branch Performance**:
```php
$branchQuery = Branch::query();
if ($isCashier && $branchId) {
    $branchQuery->where('id', $branchId); // Only their branch
}
```

6. **Scope Latest Transactions**:
```php
$latestTransactionsQuery = Order::with(['branch', 'items.menu']);
if ($isCashier && $branchId) {
    $latestTransactionsQuery->where('branch_id', $branchId);
}
```

7. **Scope Weekly Sales Chart**:
```php
$weeklySalesQuery = Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
    ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
if ($isCashier && $branchId) {
    $weeklySalesQuery->where('branch_id', $branchId);
}
```

**Dashboard Views**:

| Metric | Admin View | Cashier View |
|--------|------------|--------------|
| Total Income | All branches | Their branch only |
| Total Transactions | All branches | Their branch only |
| Active Branches | All (e.g., 5 branches) | 1 (their branch) |
| Top Selling Menus | All branches | Their branch only |
| Branch Performance | All branches | Their branch only |
| Latest Transactions | All branches | Their branch only |
| Weekly Sales Chart | All branches | Their branch only |

---

## 5. Route Protection & Security

### ✅ Protected Routes
**File**: `routes/web.php`

**Admin-Only Routes** (with `role:admin` middleware):
```php
// Reports - Admin Only
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/reports/transactions', [ReportController::class, 'transactions']);
});

// Branch Management - Admin Only
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('branch', [ManajemenCabangController::class, 'index']);
    Route::post('branch', [ManajemenCabangController::class, 'store']);
    Route::put('branch/{branch}', [ManajemenCabangController::class, 'update']);
    Route::delete('branch/{branch}', [ManajemenCabangController::class, 'destroy']);
});

// Menu Management - Admin Only
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/menu', [ManajemenMenuController::class, 'index']);
    Route::post('/menu', [ManajemenMenuController::class, 'storeMenu']);
    // ... etc
});

// Employee Management - Admin Only
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/karyawan', [KaryawanController::class, 'index']);
    Route::post('/karyawan', [KaryawanController::class, 'store']);
    // ... etc
});
```

**Public Routes** (accessible to both):
```php
// Dashboard - Both can access (data scoped in controller)
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified']);

// POS - Both can access
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pos', [AdminController::class, 'index']);
    Route::post('/pos/order', [AdminController::class, 'storeOrder']);
});
```

---

## 6. Security Testing

### ✅ URL Guessing Protection

If a **cashier** tries to access admin routes directly:

**Example**:
```
GET /branch          → 403 Forbidden (Akses ditolak)
GET /menu            → 403 Forbidden
GET /karyawan        → 403 Forbidden
GET /reports/...     → 403 Forbidden
```

**Error Response**:
```
403 | Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.
```

---

## Testing Guide

### Test as Admin:
1. Login as admin user
2. **Verify Sidebar**: See all menu items (Dashboard, POS, Branches, Menu, Employees, Reports)
3. **Verify Dashboard**: See all branches' data
4. **Verify Access**: Can access /branch, /menu, /karyawan, /reports

### Test as Cashier:
1. Login as cashier user (with branch_id assigned)
2. **Verify Sidebar**: See only Dashboard and POS menu items
3. **Verify Dashboard**: See only their branch's data
4. **Verify Restricted Access**: 
   - Navigate directly to `/branch` → Should see 403 error
   - Navigate to `/menu` → Should see 403 error
   - Navigate to `/karyawan` → Should see 403 error
   - Navigate to `/reports/transactions` → Should see 403 error

### Test Dashboard Data Scoping:
**Create test transactions**:
1. Login as cashier at "Cabang Kutoarjo" (branch_id: 1)
2. Create 3 transactions in POS
3. Check Dashboard:
   - Should see 3 transactions today
   - Should see revenue from those 3 transactions only
   - Should see "1 Cabang Aktif"
   - Weekly chart should show only Kutoarjo's sales
4. Login as admin
5. Check Dashboard:
   - Should see totals from ALL branches
   - Should see "5 Cabang Aktif" (or total branch count)
   - Weekly chart shows all branches combined

---

## Summary of Changes

| File | Type | Description |
|------|------|-------------|
| `app/Http/Middleware/CheckRole.php` | **NEW** | Role-based middleware |
| `bootstrap/app.php` | Modified | Registered middleware alias |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modified | Added role & permissions to shared props |
| `resources/js/types/index.d.ts` | Modified | Added Auth.permissions & NavItem.permission |
| `resources/js/components/AppSidebar.vue` | Modified | Dynamic menu filtering based on permissions |
| `app/Http/Controllers/DashboardController.php` | Modified | Branch-scoped data for cashiers |
| `routes/web.php` | Modified | Protected admin routes with `role:admin` |

---

## Security Features ✅

1. **Middleware Protection**: All admin routes protected by `role:admin` middleware
2. **403 Forbidden**: Cashiers attempting to access admin URLs get proper error
3. **Data Scoping**: Cashiers see only their branch's data in queries
4. **UI Hiding**: Sidebar dynamically hides unauthorized menu items
5. **Shared Permissions**: Permissions available globally in Vue via `$page.props.auth.permissions`

---

## Next Steps (Optional Enhancements)

1. **Policy Classes**: Create Laravel Policies for finer-grained control
2. **Audit Logging**: Track who accessed what and when
3. **More Roles**: Add "Manager" role with different permissions
4. **Granular Permissions**: Per-branch access control for multi-branch managers
5. **Permission UI**: Admin interface to manage roles & permissions

---

**RBAC Implementation Complete!** 🎉

Your POS application now has comprehensive role-based access control with:
- ✅ Middleware protection
- ✅ Data scoping
- ✅ UI hiding
- ✅ Security against URL guessing
