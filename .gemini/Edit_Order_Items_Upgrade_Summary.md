# Edit Order Items Upgrade - Product Swap Feature

## Overview
Successfully upgraded the "Edit Order Items" feature to allow admins to **change the product itself**, not just quantities. Admins can now swap "Roti Coklat" for "Roti Keju" or any other menu item with automatic price updates.

## Changes Made

### 1. Backend Updates (AdminController)

**File**: `app/Http/Controllers/Admin/AdminController.php`

#### Updated: `updateItems()` Method

**New Validation**:
```php
'items.*.menu_id' => ['nullable', 'integer', 'exists:menus,id']
```

**Enhanced Logic**:
1. ✅ **Accept menu_id** in request payload
2. ✅ **Detect menu change**: Check if new menu_id differs from current
3. ✅ **Fetch fresh price**: If menu changed, look up price from `menus` table
4. ✅ **Fetch fresh name**: Update item_name to match new menu
5. ✅ **Recalculate subtotal**: `new_price × new_quantity`
6. ✅ **Update order_items**: Save new menu_id, item_name, price, quantity, subtotal
7. ✅ **Recalculate order total**: Sum all item subtotals + tax

**Price Lookup Logic**:
```php
// If menu_id is provided and different from current
if (isset($itemData['menu_id']) && $itemData['menu_id'] !== $orderItem->menu_id) {
    $menu = Menu::findOrFail($itemData['menu_id']);
    $newPrice = $menu->harga;         // Fresh price from DB
    $newItemName = $menu->nama;        // Fresh name from DB
    $newMenuId = $menu->id;
}
```

**Updated Fields in order_items**:
- `menu_id` - New product ID
- `item_name` - New product name
- `price` - Fresh price from menus table
- `quantity` - New quantity
- `subtotal` - Recalculated (price × quantity)

#### Updated: `history()` Method

**New Data Passed to Frontend**:
```php
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
    })
```

### 2. Frontend Updates (History/Index.vue)

**File**: `resources/js/pages/admin/History/Index.vue`

#### New Interface
```typescript
interface Menu {
    id: number;
    nama: string;
    harga: number;
    category_name: string;
}
```

#### Updated Props
```typescript
const props = defineProps<{
    transactions: {...};
    menus: Menu[];  // ← New prop
}>();
```

#### Enhanced Edit Form State
```typescript
const editItems = ref<Array<{
    order_item_id: number,
    menu_id: number | null,  // ← New field
    quantity: number,
    price: number            // ← New field (reactive)
}>>([]);
```

#### New Function: `handleMenuChange()`
```typescript
const handleMenuChange = (index: number, menuId: number) => {
    const menu = props.menus.find(m => m.id === menuId);
    if (menu) {
        editItems.value[index].menu_id = menu.id;
        editItems.value[index].price = menu.harga;  // ← Auto-update price
    }
};
```

#### Updated Total Calculation
```typescript
const calculateNewTotal = computed(() => {
    let subtotal = 0;
    editItems.value.forEach((editItem) => {
        const price = editItem.price;      // ← Use reactive price
        const qty = editItem.quantity;
        subtotal += price * qty;
    });
    
    const tax = subtotal * 0.10;
    return subtotal + tax;
});
```

#### Enhanced Modal UI

**Product Selection Dropdown**:
```vue
<select 
    v-model="editItems[index].menu_id" 
    @change="handleMenuChange(index, editItems[index].menu_id!)"
    class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border...">
    <option v-for="menu in menus" :key="menu.id" :value="menu.id">
        {{ menu.nama }} - {{ formatRupiah(menu.harga) }}
    </option>
</select>
```

**Layout Changes**:
- **Grid Layout**: Price and Quantity side-by-side
- **Unit Price Display**: Shows current price (reactive)
- **Subtotal**: Updates in real-time when menu or quantity changes
- **Previous Order Info**: Shows original item, quantity, and subtotal

## User Experience

### Before (Old Version)
```
Item: Roti Coklat (fixed, can't change)
Quantity: [2] ← only this was editable
Price: Rp 5,000
```

### After (New Version)
```
Product: [Dropdown: Roti Keju - Rp 7,000] ← selectable
Price: Rp 7,000 ← auto-updates when product changes
Quantity: [2] ← editable
Subtotal: Rp 14,000 ← recalculates in real-time
```

## Step-by-Step Admin Workflow

1. **Open Edit Modal** - Click "Edit Items" button
2. **See Current Items** - Each item shown in a card
3. **Select New Product** - Click dropdown, choose different menu item
4. **Price Auto-Updates** - Unit price immediately changes to new product's price
5. **Subtotal Recalculates** - Shown in real-time (price × quantity)
6. **Adjust Quantity** - Change quantity if needed
7. **Preview New Total** - Orange box shows old vs new total
8. **Enter Reason** - Required explanation field
9. **Save** - Click "Simpan Perubahan"
10. **Backend Processing**:
    - Validates menu_id exists
    - Fetches fresh price from menus table
    - Updates order_item with new menu, name, price
    - Recalculates all totals
    - Records audit trail
11. **Success** - Modal closes, table refreshes, "DIEDIT" badge appears

## Example Scenario

### Situation
Customer ordered "Roti Coklat" but meant to order "Roti Keju"

### Admin Actions
1. Opens order in edit modal
2. Clicks product dropdown for first item
3. Selects "Roti Keju - Rp 7,000"
4. Price automatically updates from Rp 5,000 to Rp 7,000
5. Subtotal recalculates: 2 × Rp 7,000 = Rp 14,000
6. Enters reason: "Koreksi produk - pelanggan salah pesan"
7. Clicks save

### Backend Processing
```php
// Old values
menu_id: 1
item_name: "Roti Coklat"
price: 5000
quantity: 2
subtotal: 10000

// New values (after lookup)
menu_id: 2
item_name: "Roti Keju"  ← fetched from menus table
price: 7000              ← fetched from menus table
quantity: 2
subtotal: 14000          ← recalculated

// Order total updates
old_total: Rp 11,000 (with tax)
new_total: Rp 15,400 (with tax)
```

### Result
- Order item now shows "Roti Keju"
- Price updated to correct amount
- Total reflects new pricing
- Audit trail shows: "Koreksi produk - pelanggan salah pesan"
- "DIEDIT" badge visible to all users

## Reactive Features

### Real-Time Price Updates
✅ When menu selected → price updates **immediately**  
✅ When quantity changed → subtotal updates **immediately**  
✅ When any item changes → order total updates **immediately**  

### Visual Feedback
✅ **Unit Price Box** - Shows current price for selected product  
✅ **Subtotal** - Right-aligned, updates in real-time  
✅ **Total Preview** - Orange box shows before/after comparison  
✅ **Previous Order** - Gray text shows what was originally ordered  

## Data Flow

```
1. User selects new menu from dropdown
   ↓
2. handleMenuChange() triggered
   ↓
3. Find menu in props.menus array
   ↓
4. Update editItems[index].menu_id
   ↓
5. Update editItems[index].price (from menu.harga)
   ↓
6. calculateNewTotal computed property recalculates
   ↓
7. UI updates (price box, subtotal, total preview)
   ↓
8. User clicks save
   ↓
9. Request sent with menu_id to backend
   ↓
10. Backend validates menu_id exists
   ↓
11. Backend fetches fresh price from database
   ↓
12. Backend updates order_item record
   ↓
13. Backend recalculates order total
   ↓
14. Success response → table refreshes
```

## Validation & Security

### Frontend Validation
✅ Menu_id must be selected (from dropdown)  
✅ Quantity must be ≥ 1  
✅ Edit reason required  

### Backend Validation
✅ `menu_id` must exist in `menus` table  
✅ `order_item_id` must exist in `order_items` table  
✅ Item must belong to the order being edited  
✅ Only admins can access endpoint (middleware)  

### Data Integrity
✅ Transaction wrapper - rollback on error  
✅ Fresh price always from database  
✅ Item name synced with menu name  
✅ All totals recalculated atomically  

## Technical Improvements

### Searchable Dropdown
While not a third-party library, the native `<select>` is enhanced with:
- Full menu name + price display
- Alphabetically sorted (via `orderBy('nama')`)
- Only in-stock items shown (`where('stok', '>', 0)`)
- Category information available (for future grouping)

### Performance
✅ Menus loaded once per page load  
✅ Reactive updates without API calls  
✅ Only one database update on save  
✅ Efficient computed properties  

## Files Modified

1. ✅ `app/Http/Controllers/Admin/AdminController.php`
   - Updated `updateItems()` method
   - Updated `history()` method
   
2. ✅ `resources/js/pages/admin/History/Index.vue`
   - Added Menu interface
   - Updated props and edit form state
   - Added `handleMenuChange()` function
   - Redesigned modal UI with dropdown
   - Enhanced reactive calculations

3. ✅ `.gemini/Edit_Order_Items_Upgrade_Summary.md`
   - This documentation

## Testing Checklist

### Frontend Testing
- [ ] Dropdown shows all available menus
- [ ] Dropdown displays menu name + price
- [ ] Selecting menu updates price immediately
- [ ] Price box shows correct unit price
- [ ] Quantity input works
- [ ] Subtotal recalculates when menu changes
- [ ] Subtotal recalculates when quantity changes
- [ ] Total preview shows correct before/after
- [ ] Previous order info displays correctly
- [ ] Save button validates reason field

### Backend Testing
- [ ] Menu_id is accepted in request
- [ ] Fresh price fetched from menus table
- [ ] Fresh name fetched from menus table
- [ ] Order_item updates with new menu_id
- [ ] Order_item updates with new item_name
- [ ] Order_item updates with new price
- [ ] Subtotal recalculates correctly
- [ ] Order total recalculates correctly
- [ ] Audit trail records change
- [ ] Error handling for invalid menu_id
- [ ] Validation ensures item belongs to order

### Integration Testing
- [ ] Change product only → saves correctly
- [ ] Change quantity only → saves correctly
- [ ] Change both product and quantity → saves correctly
- [ ] Multiple items changed → all save correctly
- [ ] Price from database matches frontend display
- [ ] Order total matches calculation
- [ ] "DIEDIT" badge appears after edit
- [ ] Tooltip shows correct edit reason

### Edge Cases
- [ ] Menu with 0 stock doesn't appear in dropdown
- [ ] Menu deleted after modal opened → error handled
- [ ] Same product selected → no duplicate update
- [ ] Quantity = 1 works
- [ ] Very long menu names display properly
- [ ] Very high prices display properly

## Future Enhancements (Optional)

### Dropdown Improvements
- [ ] Group menus by category (optgroups)
- [ ] Add menu icons/emojis
- [ ] Integrate vue-select for better search
- [ ] Show stock availability in dropdown
- [ ] Disable out-of-stock items

### Price Features
- [ ] Show price change percentage
- [ ] Warn if new price significantly different
- [ ] Allow manual price override (with reason)
- [ ] Show price history

### UX Enhancements
- [ ] Undo button for accidental menu changes
- [ ] Keyboard shortcuts for common actions
- [ ] Bulk edit multiple items
- [ ] Copy from another order
- [ ] Duplicate item functionality

## Notes

- **Only in-stock menus** are shown in dropdown
- **Fresh prices** are always fetched from database on save
- **Item names** automatically sync with menu names
- **No manual price input** - ensures data consistency
- **Real-time preview** prevents surprises before save
- **Audit trail** records all changes for transparency
