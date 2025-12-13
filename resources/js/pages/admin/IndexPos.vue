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
      <div class="flex-1 flex flex-col h-full overflow-hidden" :class="{ 'hidden md:flex': mobileTab === 'cart' }">
        <!-- Search Bar - Premium Styling -->
        <div class="mb-6 relative group">
          <Search
            class="absolute left-4 top-1/2 -translate-y-1/2 h-[18px] w-[18px] text-slate-400 dark:text-slate-500" />
          <input v-model="searchQuery" type="text" placeholder="Cari sesuatu..."
            class="pl-11 pr-4 py-3 w-full md:w-72 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:focus:ring-orange-500/30 shadow-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition-all" />
        </div>

        <!-- Branch Selector (Admin Only) -->
        <div v-if="currentUser.role === 'admin'" class="mb-4">
          <div
            class="bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl p-4">
            <div class="flex items-start gap-3 mb-3">
              <div
                class="h-10 w-10 bg-orange-100 dark:bg-orange-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <Building2 class="h-5 w-5 text-orange-600 dark:text-orange-400" />
              </div>
              <div class="flex-1 min-w-0">
                <label for="branchSelect" class="block text-sm font-medium text-orange-900 dark:text-orange-200 mb-1">
                  Pilih Cabang
                </label>
                <select id="branchSelect" v-model="selectedBranchId"
                  class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-orange-300 dark:border-orange-500/30 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-zinc-900 dark:text-white"
                  :class="!selectedBranchId ? 'text-zinc-400' : ''">
                  <option :value="null" disabled>-- Pilih Cabang --</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.nama }}
                  </option>
                </select>
              </div>
            </div>
            <div v-if="selectedBranchName" class="flex items-center gap-2 pl-13">
              <Badge class="bg-orange-600 text-white hover:bg-orange-700">
                Mode: {{ selectedBranchName }}
              </Badge>
            </div>
            <p v-else class="text-xs text-orange-700 dark:text-orange-300 pl-13">
              ⚠️ Wajib pilih cabang sebelum checkout
            </p>
          </div>
        </div>

        <!-- Category Filters - Premium Pills -->
        <div class="flex gap-3 mb-6 overflow-x-auto pb-4 no-scrollbar">
          <button @click="posCategory = 'all'" :class="posCategory === 'all'
            ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg scale-105'
            : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'
            " class="px-5 py-2.5 rounded-2xl text-sm font-bold whitespace-nowrap transition-all">
            Semua Menu
          </button>
          <button v-for="cat in categories" :key="cat.id" @click="posCategory = cat.id" :class="posCategory === cat.id
            ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg scale-105'
            : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'
            " class="px-5 py-2.5 rounded-2xl text-sm font-bold whitespace-nowrap transition-all">
            {{ cat.icon }} {{ cat.nama }}
          </button>
        </div>

        <!-- Products Grid -->
        <div
          class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 overflow-y-auto pb-20 md:pb-0 pr-2 custom-scrollbar">
          <!-- Custom Order Card -->
          <div @click="openCustomOrderModal"
            class="bg-white dark:bg-zinc-900/20 border-2 border-dashed border-zinc-300 dark:border-zinc-800 p-4 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/40 hover:border-orange-500/50 cursor-pointer transition-all group flex flex-col items-center justify-center text-center h-full min-h-[14rem]">
            <div
              class="h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full mb-3 flex items-center justify-center text-zinc-400 group-hover:text-orange-500 group-hover:scale-110 transition-all">
              <Edit3 class="h-6 w-6" />
            </div>
            <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">
              Custom Order
            </h4>
            <span class="text-xs text-zinc-500 mt-1">Input item manual</span>
          </div>

          <!-- Product Cards - Premium Design -->
          <div v-for="product in filteredPosProducts" :key="product.id" @click="addToCart(product)"
            class="group relative bg-white dark:bg-slate-800/50 rounded-[2rem] p-5 cursor-pointer border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200 ease-out transform-gpu will-change-transform flex flex-col h-full justify-between active:scale-[0.98]">
            <div>
              <div
                class="aspect-square bg-slate-50 dark:bg-slate-800 rounded-[1.5rem] flex items-center justify-center text-6xl mb-4 group-hover:scale-105 transition-transform overflow-hidden relative">
                <div
                  class="absolute inset-0 bg-orange-500/0 group-hover:bg-orange-500/5 dark:group-hover:bg-orange-500/10 transition-colors">
                </div>
                <span class="drop-shadow-sm relative z-10">{{ product.icon || "🍞" }}</span>
              </div>
              <h4 class="font-bold text-slate-800 dark:text-white leading-tight mb-1 text-sm line-clamp-2">
                {{ product.nama }}
              </h4>
              <p class="text-xs text-slate-400 dark:text-slate-500 line-clamp-1">{{ product.category?.nama }}</p>
            </div>
            <div class="flex justify-between items-center mt-3">
              <span class="font-bold text-orange-600 dark:text-orange-400 text-sm">{{ formatRupiah(product.harga)
              }}</span>
              <button
                class="w-8 h-8 rounded-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 flex items-center justify-center opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all shadow-lg">
                <Plus class="w-4 h-4" />
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

      <!-- Cart Panel - Glassmorphism + Sticky -->
      <div class="w-full md:w-[26rem] flex flex-col h-[calc(100vh-10rem)] overflow-hidden
                sticky top-6 self-start
                transform-gpu will-change-transform
                bg-white/95 md:bg-white/60 dark:bg-slate-900/95 md:dark:bg-slate-900/70 md:backdrop-blur-xl
                border border-white/40 dark:border-slate-700/50
                rounded-[2rem]
                shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]"
        :class="{ 'hidden md:flex': mobileTab === 'menu' }">
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

    <!-- Mobile Sticky Footer -->
    <div v-if="mobileTab === 'menu'"
      class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 p-4 z-50 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
      <div @click="mobileTab = 'cart'" class="flex items-center gap-4 cursor-pointer">
        <!-- Cart Summary Trigger -->
        <div class="flex-1 flex items-center gap-3">
          <div
            class="h-12 w-12 flex flex-col items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800 text-orange-600 dark:text-orange-400">
            <ShoppingCart class="h-6 w-6" />
          </div>
          <div class="flex flex-col">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
              {{ totalItems }} Item
            </p>
            <p class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
              {{ formatRupiah(grandTotal) }}
            </p>
          </div>
        </div>

        <!-- Checkout Button (Visual) -->
        <button
          class="h-12 px-6 rounded-xl font-bold text-base shadow-lg shadow-orange-500/20 bg-orange-600 hover:bg-orange-700 text-white flex items-center gap-2">
          <span>Lihat</span>
          <CreditCard class="h-4 w-4" />
        </button>
      </div>
    </div>

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
