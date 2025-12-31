<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ShoppingCart, CreditCard, PlusCircle, Edit3, Trash2, Plus, Minus, Search, Menu as MenuIcon, X, CheckCircle, Printer, Building2 } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface CategoryData {
  id: number;
  nama: string;
  icon: string | null;
}

interface MenuData {
  id: number;
  category_id: number;
  nama: string;
  harga: number;
  icon: string | null;
  category: CategoryData;
}

interface BranchData {
  id: number;
  nama: string;
}

interface CartItem {
  id: number;
  name: string;
  price: number;
  qty: number;
  icon: string;
  is_custom?: boolean;
  note?: string;
}

interface PaymentMethod {
  id: string;
  name: string;
  description: string;
  details: string;
  icon: string;
}

const props = defineProps<{
  menus: MenuData[];
  categories: CategoryData[];
  branches: BranchData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Point of Sales',
    href: '/admin/pos',
  },
];

// Reactive State
const cart = ref<CartItem[]>([]);
const searchQuery = ref('');
const posCategory = ref<number | 'all'>('all');
const mobileTab = ref<'menu' | 'cart'>('menu');
const orderNumber = ref(Math.floor(Math.random() * 9000) + 1000);
const isPaymentScreenOpen = ref(false);
const isCustomOrderModalOpen = ref(false);
const showMobileCart = ref(false);
const customOrderForm = ref({
  itemName: '',
  itemPrice: 0,
  itemNote: '',
});

// Branch selection (Admin only)
const selectedBranchId = ref<number | null>(null);

// Get current user
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

// Get selected branch name for display
const selectedBranchName = computed(() => {
  if (!selectedBranchId.value) return null;
  const branch = props.branches.find(b => b.id === selectedBranchId.value);
  return branch?.nama || null;
});

const paymentMethods: PaymentMethod[] = [
  {
    id: 'cash',
    name: 'Tunai',
    description: 'Bayar langsung di kasir',
    details: 'Serahkan uang tunai dan terima struk',
    icon: '💵',
  },
  {
    id: 'bca_va',
    name: 'BCA Virtual Account',
    description: 'Pembayaran via ATM / mBanking',
    details: 'VA otomatis terisi di sistem',
    icon: '🏦',
  },
  {
    id: 'bri_va',
    name: 'BRI Virtual Account',
    description: 'Transfer dari BRImo / ATM',
    details: 'Cocok untuk pelanggan BRI',
    icon: '🏧',
  },
  {
    id: 'gopay',
    name: 'GoPay',
    description: 'Scan QRIS via GoPay',
    details: 'Notifikasi realtime',
    icon: '📱',
  },
  {
    id: 'ovo',
    name: 'OVO',
    description: 'Pembayaran dompet digital',
    details: 'Poin langsung masuk',
    icon: '💜',
  },
];

const selectedPaymentMethod = ref(paymentMethods[0].id);

// Filtered products based on category and search
const filteredPosProducts = computed(() => {
  let result = props.menus;

  if (posCategory.value !== 'all') {
    result = result.filter(menu => menu.category_id === posCategory.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(menu =>
      menu.nama.toLowerCase().includes(query) ||
      menu.category.nama.toLowerCase().includes(query)
    );
  }

  return result;
});

// Cart calculations
const subtotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.price * item.qty), 0);
});

const tax = computed(() => {
  return 0;
});

const grandTotal = computed(() => {
  return subtotal.value + tax.value;
});

const totalItems = computed(() => {
  return cart.value.reduce((sum, item) => sum + item.qty, 0);
});


// Format currency
const formatRupiah = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
};

const openCustomOrderModal = () => {
  customOrderForm.value = {
    itemName: '',
    itemPrice: 0,
    itemNote: '',
  };
  isCustomOrderModalOpen.value = true;
};

const closeCustomOrderModal = () => {
  isCustomOrderModalOpen.value = false;
  customOrderForm.value = {
    itemName: '',
    itemPrice: 0,
    itemNote: '',
  };
};

const addCustomOrderToCart = () => {
  // Allow price to be 0 or greater (for bonuses)
  if (!customOrderForm.value.itemName || customOrderForm.value.itemPrice < 0) {
    return;
  }

  cart.value.push({
    id: Date.now(),
    name: customOrderForm.value.itemName,
    price: customOrderForm.value.itemPrice,
    qty: 1,
    icon: '📝',
    is_custom: true,
    note: customOrderForm.value.itemNote || undefined,
  });

  closeCustomOrderModal();
};

// Add to cart
const addToCart = (product: MenuData) => {
  const existingItem = cart.value.find(item => item.id === product.id);
  if (existingItem) {
    existingItem.qty++;
  } else {
    cart.value.push({
      id: product.id,
      name: product.nama,
      price: product.harga,
      qty: 1,
      icon: product.icon || '🍞',
    });
  }
  // Optional: Show toast or feedback
};

// Update quantity
const updateQty = (index: number, delta: number) => {
  const item = cart.value[index];
  if (item) {
    item.qty += delta;
    if (item.qty <= 0) {
      cart.value.splice(index, 1);
    }
  }
};

// Remove from cart
const removeFromCart = (index: number) => {
  cart.value.splice(index, 1);
};

// Reset cart
const resetCart = () => {
  cart.value = [];
  orderNumber.value = Math.floor(Math.random() * 9000) + 1000;
  selectedPaymentMethod.value = paymentMethods[0].id;
  isPaymentScreenOpen.value = false;
  isSuccess.value = false;
};

const isSuccess = ref(false);

const printReceipt = () => {
  window.print();
};

// Gradient colors for product cards
const getGradientColor = (categoryId: number) => {
  const colors = [
    'from-orange-500/20',
    'from-purple-500/20',
    'from-amber-500/20',
    'from-yellow-500/20',
    'from-blue-500/20',
    'from-green-500/20',
    'from-pink-500/20',
    'from-red-500/20',
  ];
  return colors[categoryId % colors.length];
};

const openPaymentScreen = () => {
  if (cart.value.length === 0) return;
  isPaymentScreenOpen.value = true;
};

const closePaymentScreen = () => {
  isPaymentScreenOpen.value = false;
};

const selectPaymentMethod = (methodId: string) => {
  selectedPaymentMethod.value = methodId;
};

// Helper function to get cookie value
const getCookie = (name: string): string | undefined => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop()?.split(';').shift();
  }
  return undefined;
};

const confirmPayment = async () => {
  if (cart.value.length === 0) return;

  // Admin must select a branch
  if (currentUser.value.role === 'admin' && !selectedBranchId.value) {
    alert('Admin harus memilih cabang terlebih dahulu');
    return;
  }

  try {
    // Prepare order data
    const orderData: any = {
      items: cart.value.map(item => ({
        id: item.id,
        name: item.name,
        price: item.price,
        qty: item.qty,
        is_custom: item.is_custom || item.icon === '📝', // Custom orders have this icon
        note: item.note || null,
      })),
      subtotal: subtotal.value,
      tax: tax.value,
      total: grandTotal.value,
      payment_method: selectedPaymentMethod.value,
    };

    // Include branch_id for admin
    if (currentUser.value.role === 'admin' && selectedBranchId.value) {
      orderData.branch_id = Number(selectedBranchId.value);
      console.log('Admin branch selected:', orderData.branch_id);
    }

    // Get fresh CSRF token from cookie to prevent 419 errors
    const xsrfToken = getCookie('XSRF-TOKEN');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Build headers with both token options
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    // Prefer XSRF-TOKEN from cookie (it's always fresh)
    if (xsrfToken) {
      headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
    } else if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }

    // Send to backend
    const response = await fetch('/pos/order', {
      method: 'POST',
      headers,
      body: JSON.stringify(orderData),
      credentials: 'same-origin', // Ensure cookies are sent
    });

    const result = await response.json();

    if (result.success) {
      isSuccess.value = true;
    } else {
      alert('Gagal menyimpan pesanan: ' + (result.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error submitting order:', error);
    alert('Terjadi kesalahan saat menyimpan pesanan. Silakan coba lagi.');
  }
};

const closeSuccess = () => {
  resetCart();
};
</script>

<template>

  <Head title="Point of Sales" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content Wrapper -->
    <div class="h-full flex flex-col md:flex-row gap-6 fade-in pb-16 md:pb-0 items-start">
      <!-- Product Grid Section -->
      <div class="flex-1 flex flex-col w-full" :class="{ 'hidden md:flex': mobileTab === 'cart' }">
        <!-- ===== MOBILE-FIRST RESPONSIVE HEADER ===== -->
        <div class="flex flex-col gap-4 mb-6 w-full">

          <!-- Row 1: Search + Branch (Stacked Mobile, Row Desktop) -->
          <div class="flex flex-col md:flex-row gap-3 w-full items-stretch">

            <!-- Search Bar - Full Width Mobile, Flex-1 Desktop -->
            <div class="w-full md:flex-1 relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search class="h-5 w-5 text-slate-400" />
              </div>
              <input type="text" v-model="searchQuery" placeholder="Cari menu..."
                class="pl-10 pr-4 py-3 w-full h-12 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm transition-all" />
            </div>

            <!-- Branch Selector - Full Width Mobile, Fixed Width Desktop -->
            <div v-if="currentUser.role === 'admin'" class="w-full md:w-72 shrink-0">
              <div
                class="w-full h-12 flex items-center gap-2 px-3 bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-2xl">
                <div
                  class="h-8 w-8 bg-orange-100 dark:bg-orange-500/20 rounded-lg flex items-center justify-center shrink-0">
                  <Building2 class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                </div>
                <select v-model="selectedBranchId"
                  class="flex-1 min-w-0 h-8 px-2 bg-transparent border-0 text-sm font-medium focus:ring-0 focus:outline-none text-slate-900 dark:text-white cursor-pointer appearance-none"
                  :class="!selectedBranchId ? 'text-slate-400' : ''">
                  <option :value="null" disabled>Pilih Cabang...</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.nama }}
                  </option>
                </select>
                <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
              <p v-if="!selectedBranchId" class="text-xs text-orange-600 dark:text-orange-400 mt-1.5 pl-1">
                ⚠️ Wajib pilih cabang sebelum checkout
              </p>
            </div>
          </div>

          <!-- Row 2: Category Pills - Edge-to-Edge Scroll on Mobile -->
          <div class="w-full overflow-x-auto pb-2 scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
            <div class="flex gap-3 min-w-max">
              <!-- All Categories -->
              <button @click="posCategory = 'all'" :class="[
                'px-5 py-2.5 rounded-2xl text-sm font-bold whitespace-nowrap transition-all active:scale-95',
                posCategory === 'all'
                  ? 'bg-slate-900 dark:bg-orange-500 text-white shadow-lg'
                  : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'
              ]">
                Semua Menu
              </button>

              <!-- Category Loop -->
              <button v-for="cat in categories" :key="cat.id" @click="posCategory = cat.id" :class="[
                'px-5 py-2.5 rounded-2xl text-sm font-bold whitespace-nowrap transition-all active:scale-95 flex items-center gap-1.5',
                posCategory === cat.id
                  ? 'bg-slate-900 dark:bg-orange-500 text-white shadow-lg'
                  : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'
              ]">
                <span>{{ cat.icon }}</span>
                <span>{{ cat.nama }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Products Grid -->
        <div
          class="flex-1 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4 overflow-y-auto pb-32 custom-scrollbar">
          <!-- Custom Order Card -->
          <div @click="openCustomOrderModal"
            class="bg-white dark:bg-zinc-900/20 border-2 border-dashed border-zinc-300 dark:border-zinc-800 p-3 md:p-4 rounded-xl md:rounded-2xl hover:bg-zinc-50 dark:hover:bg-zinc-800/40 hover:border-orange-500/50 cursor-pointer transition-all group flex flex-col items-center justify-center text-center min-h-[10rem] md:min-h-[14rem]">
            <div
              class="h-12 w-12 md:h-16 md:w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full mb-2 md:mb-3 flex items-center justify-center text-zinc-400 group-hover:text-orange-500 group-hover:scale-110 transition-all">
              <Edit3 class="h-5 w-5 md:h-6 md:w-6" />
            </div>
            <h4 class="text-xs md:text-sm font-medium text-zinc-900 dark:text-zinc-200">
              Custom Order
            </h4>
            <span class="text-[10px] md:text-xs text-zinc-500 mt-1">Input item manual</span>
          </div>

          <!-- Product Cards - Mobile Optimized -->
          <div v-for="product in filteredPosProducts" :key="product.id" @click="addToCart(product)"
            class="group relative bg-white dark:bg-slate-800/50 rounded-xl md:rounded-[1.5rem] p-3 md:p-4 cursor-pointer border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg transition-all duration-200 ease-out flex flex-col justify-between active:scale-[0.98] min-h-[10rem] md:min-h-[14rem]">
            <div>
              <div
                class="aspect-square bg-slate-50 dark:bg-slate-800 rounded-xl md:rounded-2xl flex items-center justify-center text-4xl md:text-5xl mb-2 md:mb-3 group-hover:scale-105 transition-transform overflow-hidden relative">
                <div
                  class="absolute inset-0 bg-orange-500/0 group-hover:bg-orange-500/5 dark:group-hover:bg-orange-500/10 transition-colors">
                </div>
                <span class="drop-shadow-sm relative z-10">{{ product.icon || "🍞" }}</span>
              </div>
              <h4
                class="font-bold text-slate-800 dark:text-white leading-tight mb-0.5 md:mb-1 text-xs md:text-sm line-clamp-2">
                {{ product.nama }}
              </h4>
              <p class="text-[10px] md:text-xs text-slate-400 dark:text-slate-500 line-clamp-1">{{
                product.category?.nama }}</p>
            </div>
            <div class="flex justify-between items-center mt-2 md:mt-3">
              <span class="font-bold text-orange-600 dark:text-orange-400 text-xs md:text-sm">{{
                formatRupiah(product.harga) }}</span>
              <button
                class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-slate-900 dark:bg-orange-500 text-white flex items-center justify-center shadow-lg md:opacity-0 md:group-hover:opacity-100 md:translate-y-2 md:group-hover:translate-y-0 transition-all">
                <Plus class="w-3.5 h-3.5 md:w-4 md:h-4" />
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredPosProducts.length === 0"
            class="col-span-full flex flex-col items-center justify-center py-16">
            <div class="h-16 w-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
              <Search class="h-8 w-8 text-zinc-400" />
            </div>
            <p class="text-zinc-500">Tidak ada menu yang ditemukan</p>
          </div>
        </div>
      </div>

      <!-- Cart Panel - Glassmorphism + Sticky (Desktop Only) - Hidden in favor of floating cart button -->
      <div class="hidden w-full lg:w-[26rem] flex-col h-[calc(100vh-10rem)] overflow-hidden
                sticky top-6 self-start
                transform-gpu will-change-transform
                bg-white/60 dark:bg-slate-900/70 backdrop-blur-xl
                border border-white/40 dark:border-slate-700/50
                rounded-[2rem]
                shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]">
        <!-- Cart Header -->
        <div
          class="p-6 border-b border-slate-100/50 dark:border-slate-700/50 bg-white/60 dark:bg-slate-900/50 backdrop-blur-sm">
          <div class="flex justify-between items-center mb-1">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Current Order</h2>
            <div
              class="px-2 py-1 bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold rounded-lg">
              #{{ orderNumber }}</div>
          </div>
          <button v-if="cart.length > 0" @click="resetCart" class="text-red-500 hover:text-red-700 text-xs font-medium">
            Reset
          </button>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
          <!-- Empty State -->
          <div v-if="cart.length === 0"
            class="h-full flex flex-col items-center justify-center text-slate-300 dark:text-slate-600 space-y-4">
            <ShoppingCart class="h-10 w-10" />
            <p class="text-sm font-medium">Keranjang Kosong</p>
          </div>

          <!-- Cart Item -->
          <div v-for="(item, index) in cart" :key="item.id"
            class="bg-white dark:bg-slate-800 p-3 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex gap-4 animate-scale-in">
            <div
              class="w-16 h-16 bg-slate-50 dark:bg-slate-700 rounded-xl flex items-center justify-center text-2xl shrink-0">
              {{ item.icon }}
            </div>
            <div class="flex-1 min-w-0 flex flex-col justify-center">
              <h4 class="font-bold text-slate-800 dark:text-white text-sm truncate">{{ item.name }}</h4>
              <p v-if="item.note" class="text-xs text-slate-400 dark:text-slate-500 italic truncate">{{ item.note }}</p>
              <p class="text-orange-600 dark:text-orange-400 text-xs font-bold">{{ formatRupiah(item.price) }}</p>
            </div>
            <div class="flex flex-col items-center gap-1 bg-slate-50 dark:bg-slate-700 rounded-lg p-1">
              <button @click.stop="updateQty(index, 1)"
                class="w-6 h-6 bg-white dark:bg-slate-600 rounded shadow-sm text-slate-800 dark:text-white flex items-center justify-center text-xs hover:bg-slate-200 dark:hover:bg-slate-500 transition-colors">
                <Plus class="w-3 h-3" />
              </button>
              <span class="text-xs font-bold text-slate-800 dark:text-white">{{ item.qty }}</span>
              <button @click.stop="updateQty(index, -1)"
                class="w-6 h-6 bg-white dark:bg-slate-600 rounded shadow-sm text-slate-400 dark:text-slate-300 flex items-center justify-center text-xs hover:text-red-500 dark:hover:text-red-400 transition-colors">
                <Minus class="w-3 h-3" />
              </button>
            </div>
          </div>
        </div>

        <!-- Cart Footer -->
        <div class="p-6 bg-white/50 dark:bg-slate-900/80 border-t border-slate-100/50 dark:border-slate-700/50 z-10">
          <!-- Totals -->
          <div class="space-y-3 mb-6">
            <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400 font-medium">
              <span>Subtotal</span>
              <span>{{ formatRupiah(subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400 font-medium">
              <span>Pajak (0%)</span>
              <span>{{ formatRupiah(tax) }}</span>
            </div>
            <div
              class="flex justify-between items-end pt-4 border-t border-dashed border-slate-200 dark:border-slate-700">
              <span class="text-slate-900 dark:text-white font-bold">Total</span>
              <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatRupiah(grandTotal) }}</span>
            </div>
          </div>

          <!-- Process Button -->
          <button type="button" :disabled="cart.length === 0" @click="openPaymentScreen" :class="cart.length === 0
            ? 'opacity-50 cursor-not-allowed'
            : 'hover:bg-slate-800 dark:hover:bg-orange-500 hover:scale-[1.02] active:scale-[0.98]'
            "
            class="w-full py-4 bg-slate-900 dark:bg-orange-600 text-white rounded-2xl font-bold text-lg transition-all shadow-xl shadow-slate-900/20 dark:shadow-orange-500/20 flex items-center justify-center gap-2">
            <span>Bayar</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MOBILE CART SUMMARY BAR (Floating Trigger) ===== -->
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-full opacity-0">
      <div v-if="cart.length > 0 && !showMobileCart" @click="showMobileCart = true"
        class="fixed bottom-[5.5rem] md:bottom-8 left-4 right-4 md:left-auto md:right-8 md:w-80 z-40 bg-slate-900 dark:bg-slate-800 text-white p-4 rounded-2xl shadow-xl cursor-pointer active:scale-[0.98] transition-transform">
        <div class="flex justify-between items-center">
          <!-- Left: Badge + Item Count -->
          <div class="flex items-center gap-3">
            <div class="relative">
              <ShoppingCart class="h-6 w-6" />
              <span
                class="absolute -top-2 -right-2 h-5 w-5 bg-orange-500 rounded-full text-xs font-bold flex items-center justify-center">
                {{ totalItems }}
              </span>
            </div>
            <span class="text-sm text-slate-300">{{ totalItems }} Item</span>
          </div>

          <!-- Right: Total Price -->
          <span class="text-lg font-bold">{{ formatRupiah(grandTotal) }}</span>
        </div>
      </div>
    </Transition>

    <!-- ===== MOBILE CART DRAWER (Full Screen Slide-Up Sheet) ===== -->
    <Teleport to="body">
      <!-- Overlay -->
      <Transition enter-active-class="transition-opacity duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showMobileCart" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[60]"
          @click="showMobileCart = false" />
      </Transition>

      <!-- Drawer Panel -->
      <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-y-full"
        enter-to-class="translate-y-0" leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-y-0" leave-to-class="translate-y-full">
        <div v-if="showMobileCart"
          class="fixed inset-0 md:inset-y-0 md:right-0 md:left-auto md:w-[28rem] z-[70] bg-[#F6F8FA] dark:bg-slate-900 flex flex-col shadow-2xl">
          <!-- Header -->
          <div
            class="flex items-center justify-between px-5 py-4 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="flex items-center gap-3">
              <h2 class="text-xl font-bold text-slate-900 dark:text-white">Current Order</h2>
              <div
                class="px-2 py-1 bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold rounded-lg">
                #{{ orderNumber }}
              </div>
            </div>
            <button @click="showMobileCart = false"
              class="h-10 w-10 rounded-full flex items-center justify-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
              <X class="h-5 w-5 text-slate-600 dark:text-slate-300" />
            </button>
          </div>

          <!-- Cart Items Body -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <!-- Empty State -->
            <div v-if="cart.length === 0"
              class="h-full flex flex-col items-center justify-center text-slate-300 dark:text-slate-600 space-y-4">
              <ShoppingCart class="h-16 w-16" />
              <p class="text-lg font-medium">Keranjang Kosong</p>
              <button @click="showMobileCart = false" class="text-orange-500 text-sm font-medium">
                Mulai Belanja
              </button>
            </div>

            <!-- Cart Item Cards -->
            <div v-for="(item, index) in cart" :key="item.id"
              class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex gap-4">
              <div
                class="w-16 h-16 bg-slate-50 dark:bg-slate-700 rounded-xl flex items-center justify-center text-3xl shrink-0">
                {{ item.icon }}
              </div>
              <div class="flex-1 min-w-0 flex flex-col justify-center">
                <h4 class="font-bold text-slate-800 dark:text-white text-base truncate">{{ item.name }}</h4>
                <p v-if="item.note" class="text-xs text-slate-400 dark:text-slate-500 italic truncate">{{ item.note }}
                </p>
                <p class="text-orange-600 dark:text-orange-400 text-sm font-bold">{{ formatRupiah(item.price) }}</p>
              </div>
              <!-- Quantity Controls -->
              <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-1.5">
                <button @click.stop="updateQty(index, -1)"
                  class="w-8 h-8 bg-white dark:bg-slate-600 rounded-lg shadow-sm text-slate-400 dark:text-slate-300 flex items-center justify-center hover:text-red-500 dark:hover:text-red-400 transition-colors">
                  <Minus class="w-4 h-4" />
                </button>
                <span class="w-8 text-center text-sm font-bold text-slate-800 dark:text-white">{{ item.qty }}</span>
                <button @click.stop="updateQty(index, 1)"
                  class="w-8 h-8 bg-white dark:bg-slate-600 rounded-lg shadow-sm text-slate-800 dark:text-white flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-500 transition-colors">
                  <Plus class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Footer with Totals + Checkout Button -->
          <div class="bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 p-5 pb-safe space-y-4">
            <!-- Totals -->
            <div class="space-y-2">
              <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400">
                <span>Subtotal</span>
                <span>{{ formatRupiah(subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400">
                <span>Pajak (0%)</span>
                <span>{{ formatRupiah(tax) }}</span>
              </div>
              <div
                class="flex justify-between items-end pt-3 border-t border-dashed border-slate-200 dark:border-slate-600">
                <span class="text-slate-900 dark:text-white font-bold">Total</span>
                <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatRupiah(grandTotal)
                  }}</span>
              </div>
            </div>

            <!-- Checkout Button -->
            <button type="button" :disabled="cart.length === 0" @click="showMobileCart = false; openPaymentScreen();"
              :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-600 active:scale-[0.98]'"
              class="w-full py-4 bg-orange-500 text-white rounded-2xl font-bold text-lg transition-all shadow-xl shadow-orange-500/30 flex items-center justify-center gap-2">
              <CreditCard class="h-5 w-5" />
              <span>Bayar Sekarang</span>
            </button>

            <!-- Reset Button -->
            <button v-if="cart.length > 0" @click="resetCart(); showMobileCart = false;"
              class="w-full py-3 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl font-medium text-sm transition-colors">
              Hapus Semua Item
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Payment Screen -->
    <transition name="slide-up">
      <div v-if="isPaymentScreenOpen" class="fixed inset-0 z-[70] flex flex-col">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePaymentScreen"></div>
        <div
          class="relative mt-auto w-full bg-white dark:bg-zinc-900 rounded-t-3xl md:rounded-2xl md:mx-auto md:my-12 md:max-w-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
          <!-- Success State -->
          <div v-if="isSuccess" class="flex flex-col items-center justify-center py-12 px-6 text-center h-full">
            <div
              class="h-24 w-24 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center mb-6 text-green-600 dark:text-green-400 animate-bounce">
              <CheckCircle class="h-12 w-12" />
            </div>
            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Pembayaran Berhasil!</h3>
            <p class="text-zinc-500 dark:text-zinc-400 mb-8">Transaksi sebesar <span
                class="font-bold text-zinc-900 dark:text-white">{{ formatRupiah(grandTotal) }}</span> telah berhasil
              direkam.</p>

            <div class="w-full space-y-3">
              <Button size="lg"
                class="w-full gap-2 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 h-12 text-base"
                @click="printReceipt">
                <Printer class="h-5 w-5" /> Cetak Struk
              </Button>
              <Button variant="outline" size="lg" class="w-full h-12 text-base" @click="closeSuccess">
                Transaksi Baru
              </Button>
            </div>
          </div>

          <!-- Payment Form -->
          <div v-else class="flex flex-col h-full">
            <div
              class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-zinc-100 dark:border-zinc-800 shrink-0">
              <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500">Checkout</p>
                <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Metode Pembayaran</h3>
              </div>
              <button @click="closePaymentScreen"
                class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                <X class="h-5 w-5" />
              </button>
            </div>

            <div class="px-6 py-5 space-y-6 overflow-y-auto custom-scrollbar flex-1">
              <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 bg-zinc-50 dark:bg-zinc-900/40">
                <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-300">
                  <span>Subtotal</span>
                  <span>{{ formatRupiah(subtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-300 mt-2">
                  <span>Pajak (0%)</span>
                  <span>{{ formatRupiah(tax) }}</span>
                </div>
                <div class="my-4 border-t border-dashed border-zinc-300 dark:border-zinc-700"></div>
                <div class="flex justify-between items-center">
                  <div>
                    <p class="text-xs text-zinc-500">Total Pembayaran</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatRupiah(grandTotal) }}
                    </p>
                  </div>
                  <Badge variant="secondary" class="text-[10px] uppercase tracking-wider px-2 py-1">Order #{{
                    orderNumber }}</Badge>
                </div>
              </div>

              <div class="space-y-4">
                <p class="text-xs uppercase tracking-wide text-zinc-500 font-semibold">Pilih Pembayaran</p>
                <div class="grid gap-3">
                  <div v-for="method in paymentMethods" :key="method.id" @click="selectPaymentMethod(method.id)"
                    class="border rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all active:scale-[0.98]"
                    :class="selectedPaymentMethod === method.id ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/10 ring-1 ring-orange-500' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'">
                    <div
                      class="h-14 w-14 rounded-xl bg-white dark:bg-zinc-800 flex items-center justify-center text-3xl shadow-sm">
                      {{ method.icon }}
                    </div>
                    <div class="flex-1">
                      <div class="flex justify-between items-start">
                        <p class="text-base font-bold text-zinc-900 dark:text-white">{{ method.name }}</p>
                        <div v-if="selectedPaymentMethod === method.id"
                          class="h-5 w-5 rounded-full bg-orange-500 flex items-center justify-center">
                          <CheckCircle class="h-3 w-3 text-white" />
                        </div>
                      </div>
                      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ method.description }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="px-6 pb-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 shrink-0 bg-white dark:bg-zinc-900">
              <Button class="w-full gap-2 h-14 text-lg font-bold shadow-xl shadow-orange-500/20"
                :disabled="cart.length === 0" @click="confirmPayment">
                <CreditCard class="h-5 w-5" /> Konfirmasi & Bayar
              </Button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Custom Order Modal -->
    <transition name="slide-up">
      <div v-if="isCustomOrderModalOpen" class="fixed inset-0 z-[70] flex flex-col">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeCustomOrderModal"></div>
        <div
          class="relative mt-auto w-full bg-white dark:bg-zinc-900 rounded-t-3xl md:rounded-2xl md:mx-auto md:my-12 md:max-w-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
          <div
            class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-zinc-100 dark:border-zinc-800 shrink-0">
            <div>
              <p class="text-xs uppercase tracking-wide text-zinc-500">Custom Item</p>
              <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Tambah Item Manual</h3>
            </div>
            <button @click="closeCustomOrderModal"
              class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="px-6 py-5 space-y-6 overflow-y-auto custom-scrollbar flex-1">
            <div class="space-y-4">
              <div>
                <label for="itemName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Nama Item
                </label>
                <Input id="itemName" v-model="customOrderForm.itemName" type="text"
                  placeholder="Contoh: Nasi Goreng Spesial" class="w-full" />
              </div>

              <div>
                <label for="itemPrice" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Harga (Rp)
                </label>
                <Input id="itemPrice" v-model.number="customOrderForm.itemPrice" type="number"
                  placeholder="Contoh: 25000 (atau 0 untuk bonus)" min="0" step="1000" class="w-full" />
                <p v-if="customOrderForm.itemPrice > 0" class="mt-2 text-sm text-zinc-500">
                  Preview: <span class="font-semibold text-orange-600 dark:text-orange-400">{{
                    formatRupiah(customOrderForm.itemPrice) }}</span>
                </p>
                <p v-else-if="customOrderForm.itemPrice === 0" class="mt-2 text-sm text-green-600 dark:text-green-400">
                  🎁 Item Bonus (Gratis)
                </p>
              </div>

              <div>
                <label for="itemNote" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Catatan / Alasan <span class="text-xs text-zinc-400">(Opsional)</span>
                </label>
                <textarea id="itemNote" v-model="customOrderForm.itemNote"
                  placeholder="Contoh: Bonus Ultah, Pengganti Roti Gosong, dll." rows="3"
                  class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-zinc-900 dark:text-white resize-none"></textarea>
                <p class="mt-1 text-xs text-zinc-500">
                  Tambahkan catatan jika diperlukan (misal: alasan bonus atau permintaan khusus)
                </p>
              </div>
            </div>

            <div v-if="!customOrderForm.itemName || customOrderForm.itemPrice < 0"
              class="rounded-xl border border-yellow-200 dark:border-yellow-800/30 bg-yellow-50 dark:bg-yellow-900/10 p-4">
              <p class="text-sm text-yellow-800 dark:text-yellow-200">
                ⚠️ Pastikan nama item terisi dan harga tidak negatif (harga 0 diperbolehkan untuk bonus)
              </p>
            </div>
          </div>

          <div class="px-6 pb-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 shrink-0 bg-white dark:bg-zinc-900">
            <Button class="w-full gap-2 h-14 text-lg font-bold shadow-xl shadow-orange-500/20"
              :disabled="!customOrderForm.itemName || customOrderForm.itemPrice < 0" @click="addCustomOrderToCart">
              <ShoppingCart class="h-5 w-5" /> Tambah ke Keranjang
            </Button>
          </div>
        </div>
      </div>
    </transition>
    <!-- ===== PRINTABLE RECEIPT (Hidden, visible only on print) ===== -->
    <div id="printable-receipt" class="hidden print:block font-mono text-black bg-white p-4">
      <!-- Store Header -->
      <div class="text-center mb-4">
        <h1 class="text-lg font-bold uppercase tracking-wide">Epok POS</h1>
        <p class="text-[10px] text-gray-600">Jl. Contoh Alamat No. 123</p>
        <p class="text-[10px] text-gray-600">Telp: 0812-3456-7890</p>
      </div>

      <!-- Separator -->
      <div class="border-b border-dashed border-black my-2"></div>

      <!-- Transaction Info -->
      <div class="text-[10px] space-y-1 mb-3">
        <div class="flex justify-between">
          <span>Tanggal:</span>
          <span>{{ new Date().toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric', hour:
              '2-digit', minute: '2-digit'
          }) }}</span>
        </div>
        <div class="flex justify-between">
          <span>No. Order:</span>
          <span>#{{ orderNumber }}</span>
        </div>
        <div class="flex justify-between">
          <span>Kasir:</span>
          <span>{{ currentUser?.name || 'Kasir' }}</span>
        </div>
        <div v-if="selectedBranchName" class="flex justify-between">
          <span>Cabang:</span>
          <span>{{ selectedBranchName }}</span>
        </div>
      </div>

      <!-- Separator -->
      <div class="border-b border-dashed border-black my-2"></div>

      <!-- Items List -->
      <div class="text-[10px] space-y-1 mb-3">
        <div v-for="(item, index) in cart" :key="index" class="flex justify-between">
          <div class="flex-1">
            <span class="block font-medium">{{ item.name }}</span>
            <span class="text-gray-600">{{ item.qty }} x {{ formatRupiah(item.price) }}</span>
          </div>
          <span class="font-medium">{{ formatRupiah(item.price * item.qty) }}</span>
        </div>
      </div>

      <!-- Separator -->
      <div class="border-b border-dashed border-black my-2"></div>

      <!-- Totals -->
      <div class="text-[10px] space-y-1 mb-3">
        <div class="flex justify-between">
          <span>Subtotal:</span>
          <span>{{ formatRupiah(subtotal) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Pajak (0%):</span>
          <span>{{ formatRupiah(tax) }}</span>
        </div>
        <div class="flex justify-between font-bold text-xs pt-1 border-t border-black">
          <span>TOTAL:</span>
          <span>{{ formatRupiah(grandTotal) }}</span>
        </div>
      </div>

      <!-- Separator -->
      <div class="border-b border-dashed border-black my-2"></div>

      <!-- Payment Method -->
      <div class="text-[10px] text-center mb-3">
        <p>Pembayaran: <span class="font-medium uppercase">{{ selectedPaymentMethod }}</span></p>
      </div>

      <!-- Footer -->
      <div class="text-center text-[10px] mt-4">
        <p class="font-bold">✨ Terima Kasih ✨</p>
        <p class="text-gray-600 mt-1">Selamat menikmati!</p>
        <p class="text-gray-500 mt-2 text-[8px]">WiFi: EpokPOS | Pass: Welcome123</p>
      </div>
    </div>
  </AppLayout>

</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #d4d4d8;
  border-radius: 2px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #3f3f46;
}

.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
