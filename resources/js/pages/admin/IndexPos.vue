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
    stok: number;
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

    // Send to backend
    const response = await fetch('/pos/order', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(orderData),
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
    <div class="h-full flex flex-col md:flex-row gap-6 fade-in pb-16 md:pb-0">
      <!-- Product Grid Section -->
      <div
        class="flex-1 flex flex-col h-full overflow-hidden"
        :class="{ 'hidden md:flex': mobileTab === 'cart' }"
      >
        <!-- Search Bar -->
        <div class="mb-4 relative">
          <Search
            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400"
          />
          <Input
            v-model="searchQuery"
            type="text"
            placeholder="Cari menu..."
            class="pl-9 w-full bg-white dark:bg-zinc-900"
          />
        </div>

        <!-- Branch Selector (Admin Only) -->
        <div v-if="currentUser.role === 'admin'" class="mb-4">
          <div class="bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl p-4">
            <div class="flex items-start gap-3 mb-3">
              <div class="h-10 w-10 bg-orange-100 dark:bg-orange-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <Building2 class="h-5 w-5 text-orange-600 dark:text-orange-400" />
              </div>
              <div class="flex-1 min-w-0">
                <label for="branchSelect" class="block text-sm font-medium text-orange-900 dark:text-orange-200 mb-1">
                  Pilih Cabang
                </label>
                <select
                  id="branchSelect"
                  v-model="selectedBranchId"
                  class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-orange-300 dark:border-orange-500/30 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-zinc-900 dark:text-white"
                  :class="!selectedBranchId ? 'text-zinc-400' : ''"
                >
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

        <!-- Category Filters -->
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2 no-scrollbar">
          <button
            @click="posCategory = 'all'"
            :class="
              posCategory === 'all'
                ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'
            "
            class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap shadow-sm border border-zinc-200 dark:border-zinc-800 transition-colors"
          >
            Semua Menu
          </button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            @click="posCategory = cat.id"
            :class="
              posCategory === cat.id
                ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'
            "
            class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-colors shadow-sm border border-zinc-200 dark:border-zinc-800"
          >
            {{ cat.icon }} {{ cat.nama }}
          </button>
        </div>

        <!-- Products Grid -->
        <div
          class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 overflow-y-auto pb-20 md:pb-0 pr-2 custom-scrollbar"
        >
          <!-- Custom Order Card -->
          <div
            @click="openCustomOrderModal"
            class="bg-white dark:bg-zinc-900/20 border-2 border-dashed border-zinc-300 dark:border-zinc-800 p-4 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/40 hover:border-orange-500/50 cursor-pointer transition-all group flex flex-col items-center justify-center text-center h-full min-h-[14rem]"
          >
            <div
              class="h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full mb-3 flex items-center justify-center text-zinc-400 group-hover:text-orange-500 group-hover:scale-110 transition-all"
            >
              <Edit3 class="h-6 w-6" />
            </div>
            <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">
              Custom Order
            </h4>
            <span class="text-xs text-zinc-500 mt-1">Input item manual</span>
          </div>

          <!-- Product Cards -->
          <div
            v-for="product in filteredPosProducts"
            :key="product.id"
            @click="addToCart(product)"
            class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-4 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-800/40 cursor-pointer transition-all group shadow-sm dark:shadow-none flex flex-col h-full justify-between active:scale-95"
          >
            <div>
              <div
                class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-3 flex items-center justify-center relative overflow-hidden"
              >
                <div
                  :class="`absolute inset-0 bg-gradient-to-tr ${getGradientColor(
                    product.category_id
                  )} to-transparent`"
                ></div>
                <span class="text-4xl drop-shadow-sm relative z-10">{{
                  product.icon || "🍞"
                }}</span>
              </div>
              <h4
                class="text-sm font-medium text-zinc-900 dark:text-zinc-200 line-clamp-2"
              >
                {{ product.nama }}
              </h4>
              <p class="text-xs text-zinc-500 mt-1">{{ product.category?.nama }}</p>
            </div>
            <div
              class="flex justify-between items-center mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800"
            >
              <span class="text-xs text-zinc-500">Stok: {{ product.stok }}</span>
              <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">{{
                formatRupiah(product.harga)
              }}</span>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-if="filteredPosProducts.length === 0"
            class="col-span-full flex flex-col items-center justify-center py-16"
          >
            <div
              class="h-16 w-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4"
            >
              <Search class="h-8 w-8 text-zinc-400" />
            </div>
            <p class="text-zinc-500">Tidak ada menu yang ditemukan</p>
          </div>
        </div>
      </div>

      <!-- Cart Panel -->
      <div
        class="w-full md:w-80 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col h-[calc(100vh-10rem)] shadow-sm dark:shadow-none"
        :class="{ 'hidden md:flex': mobileTab === 'menu' }"
      >
        <!-- Cart Header -->
        <div
          class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center"
        >
          <div class="flex items-center gap-3">
             <button 
              v-if="mobileTab === 'cart'"
              @click="mobileTab = 'menu'"
              class="md:hidden h-8 w-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500"
            >
              <X class="h-4 w-4" />
            </button>
            <div>
              <h3 class="font-medium text-zinc-900 dark:text-zinc-200">Pesanan Baru</h3>
              <span class="text-xs text-zinc-500">Order #{{ orderNumber }}</span>
            </div>
          </div>
          <button
            v-if="cart.length > 0"
            @click="resetCart"
            class="text-red-500 hover:text-red-700 text-xs font-medium"
          >
            Reset
          </button>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
          <!-- Empty State -->
          <div
            v-if="cart.length === 0"
            class="h-full flex flex-col items-center justify-center text-zinc-400"
          >
            <ShoppingCart class="h-8 w-8 mb-2 opacity-50" />
            <span class="text-xs">Keranjang kosong</span>
            <span class="text-xs mt-1">Klik menu untuk menambahkan</span>
          </div>

          <!-- Cart Item -->
          <div
            v-for="(item, index) in cart"
            :key="item.id"
            class="flex justify-between items-start group bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg"
          >
            <div class="flex gap-3">
              <div
                class="h-10 w-10 bg-white dark:bg-zinc-800 rounded flex items-center justify-center text-lg border border-zinc-200 dark:border-zinc-700"
              >
                {{ item.icon }}
              </div>
              <div class="flex-1">
                <p
                  class="text-sm text-zinc-900 dark:text-zinc-200 font-medium line-clamp-1"
                >
                  {{ item.name }}
                </p>
                <p v-if="item.note" class="text-xs text-zinc-500 italic mt-0.5">
                  Catatan: {{ item.note }}
                </p>
                <p class="text-xs text-zinc-500">{{ formatRupiah(item.price) }}</p>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ formatRupiah(item.price * item.qty) }}
              </p>
              <div class="flex items-center gap-1">
                <button
                  @click.stop="updateQty(index, -1)"
                  class="h-8 w-8 md:h-6 md:w-6 rounded bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600 flex items-center justify-center transition-colors"
                >
                  <Minus class="h-4 w-4 md:h-3 md:w-3" />
                </button>
                <span class="w-6 text-center text-sm font-medium">{{ item.qty }}</span>
                <button
                  @click.stop="updateQty(index, 1)"
                  class="h-8 w-8 md:h-6 md:w-6 rounded bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600 flex items-center justify-center transition-colors"
                >
                  <Plus class="h-4 w-4 md:h-3 md:w-3" />
                </button>
                <button
                  @click.stop="removeFromCart(index)"
                  class="h-8 w-8 md:h-6 md:w-6 rounded bg-red-100 dark:bg-red-500/20 hover:bg-red-200 dark:hover:bg-red-500/30 flex items-center justify-center ml-1 transition-colors"
                >
                  <Trash2 class="h-4 w-4 md:h-3 md:w-3 text-red-500" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Cart Footer -->
        <div
          class="p-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 rounded-b-xl"
        >
          <!-- Add Custom Order Button -->
          <button
            @click="openCustomOrderModal"
            class="w-full mb-4 bg-white dark:bg-zinc-800 border border-dashed border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-500 dark:text-zinc-300 text-xs font-medium py-3 md:py-2 rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <PlusCircle class="h-4 w-4 md:h-3 md:w-3" /> Tambah Custom Order
          </button>

          <!-- Totals -->
          <div class="flex justify-between mb-2 text-sm text-zinc-500 dark:text-zinc-400">
            <span>Subtotal</span>
            <span>{{ formatRupiah(subtotal) }}</span>
          </div>
          <div class="flex justify-between mb-4 text-sm text-zinc-500 dark:text-zinc-400">
            <span>Pajak (0%)</span>
            <span>{{ formatRupiah(tax) }}</span>
          </div>
          <div
            class="flex justify-between mb-6 text-base font-semibold text-zinc-900 dark:text-white"
          >
            <span>Total</span>
            <span class="text-orange-600 dark:text-orange-400">{{
              formatRupiah(grandTotal)
            }}</span>
          </div>

          <!-- Process Button -->
          <button
            type="button"
            :disabled="cart.length === 0"
            @click="openPaymentScreen"
            :class="
              cart.length === 0
                ? 'opacity-50 cursor-not-allowed'
                : 'hover:bg-zinc-800 dark:hover:bg-zinc-200'
            "
            class="w-full bg-zinc-900 dark:bg-white text-white dark:text-zinc-950 font-medium py-3 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm"
          >
            <CreditCard class="h-4 w-4" /> Proses Pembayaran
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Sticky Footer -->
    <div 
        v-if="mobileTab === 'menu'"
        class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 p-4 z-50 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]"
    >
      <div 
        @click="mobileTab = 'cart'"
        class="flex items-center gap-4 cursor-pointer"
      >
        <!-- Cart Summary Trigger -->
        <div class="flex-1 flex items-center gap-3">
          <div class="h-12 w-12 flex flex-col items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800 text-orange-600 dark:text-orange-400">
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
          class="h-12 px-6 rounded-xl font-bold text-base shadow-lg shadow-orange-500/20 bg-orange-600 hover:bg-orange-700 text-white flex items-center gap-2"
        >
            <span>Lihat</span>
            <CreditCard class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Payment Screen -->
    <transition name="slide-up">
      <div
        v-if="isPaymentScreenOpen"
        class="fixed inset-0 z-[70] flex flex-col"
      >
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePaymentScreen"></div>
        <div
          class="relative mt-auto w-full bg-white dark:bg-zinc-900 rounded-t-3xl md:rounded-2xl md:mx-auto md:my-12 md:max-w-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
        >
          <!-- Success State -->
          <div v-if="isSuccess" class="flex flex-col items-center justify-center py-12 px-6 text-center h-full">
            <div class="h-24 w-24 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center mb-6 text-green-600 dark:text-green-400 animate-bounce">
                <CheckCircle class="h-12 w-12" />
            </div>
            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Pembayaran Berhasil!</h3>
            <p class="text-zinc-500 dark:text-zinc-400 mb-8">Transaksi sebesar <span class="font-bold text-zinc-900 dark:text-white">{{ formatRupiah(grandTotal) }}</span> telah berhasil direkam.</p>
            
            <div class="w-full space-y-3">
                <Button size="lg" class="w-full gap-2 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 h-12 text-base" @click="printReceipt">
                    <Printer class="h-5 w-5" /> Cetak Struk
                </Button>
                <Button variant="outline" size="lg" class="w-full h-12 text-base" @click="closeSuccess">
                    Transaksi Baru
                </Button>
            </div>
          </div>

          <!-- Payment Form -->
          <div v-else class="flex flex-col h-full">
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-zinc-100 dark:border-zinc-800 shrink-0">
              <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500">Checkout</p>
                <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Metode Pembayaran</h3>
              </div>
              <button @click="closePaymentScreen" class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
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
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatRupiah(grandTotal) }}</p>
                  </div>
                  <Badge variant="secondary" class="text-[10px] uppercase tracking-wider px-2 py-1">Order #{{ orderNumber }}</Badge>
                </div>
              </div>

              <div class="space-y-4">
                <p class="text-xs uppercase tracking-wide text-zinc-500 font-semibold">Pilih Pembayaran</p>
                <div class="grid gap-3">
                  <div
                    v-for="method in paymentMethods"
                    :key="method.id"
                    @click="selectPaymentMethod(method.id)"
                    class="border rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all active:scale-[0.98]"
                    :class="selectedPaymentMethod === method.id ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/10 ring-1 ring-orange-500' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'"
                  >
                    <div class="h-14 w-14 rounded-xl bg-white dark:bg-zinc-800 flex items-center justify-center text-3xl shadow-sm">
                      {{ method.icon }}
                    </div>
                    <div class="flex-1">
                      <div class="flex justify-between items-start">
                        <p class="text-base font-bold text-zinc-900 dark:text-white">{{ method.name }}</p>
                        <div v-if="selectedPaymentMethod === method.id" class="h-5 w-5 rounded-full bg-orange-500 flex items-center justify-center">
                          <CheckCircle class="h-3 w-3 text-white" />
                        </div>
                      </div>
                      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ method.description }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-6 pb-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 shrink-0 bg-white dark:bg-zinc-900">
              <Button class="w-full gap-2 h-14 text-lg font-bold shadow-xl shadow-orange-500/20" :disabled="cart.length === 0" @click="confirmPayment">
                <CreditCard class="h-5 w-5" /> Konfirmasi & Bayar
              </Button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Custom Order Modal -->
    <transition name="slide-up">
      <div
        v-if="isCustomOrderModalOpen"
        class="fixed inset-0 z-[70] flex flex-col"
      >
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeCustomOrderModal"></div>
        <div
          class="relative mt-auto w-full bg-white dark:bg-zinc-900 rounded-t-3xl md:rounded-2xl md:mx-auto md:my-12 md:max-w-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
        >
          <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-zinc-100 dark:border-zinc-800 shrink-0">
            <div>
              <p class="text-xs uppercase tracking-wide text-zinc-500">Custom Item</p>
              <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Tambah Item Manual</h3>
            </div>
            <button @click="closeCustomOrderModal" class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="px-6 py-5 space-y-6 overflow-y-auto custom-scrollbar flex-1">
            <div class="space-y-4">
              <div>
                <label for="itemName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Nama Item
                </label>
                <Input
                  id="itemName"
                  v-model="customOrderForm.itemName"
                  type="text"
                  placeholder="Contoh: Nasi Goreng Spesial"
                  class="w-full"
                />
              </div>

              <div>
                <label for="itemPrice" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Harga (Rp)
                </label>
                <Input
                  id="itemPrice"
                  v-model.number="customOrderForm.itemPrice"
                  type="number"
                  placeholder="Contoh: 25000 (atau 0 untuk bonus)"
                  min="0"
                  step="1000"
                  class="w-full"
                />
                <p v-if="customOrderForm.itemPrice > 0" class="mt-2 text-sm text-zinc-500">
                  Preview: <span class="font-semibold text-orange-600 dark:text-orange-400">{{ formatRupiah(customOrderForm.itemPrice) }}</span>
                </p>
                <p v-else-if="customOrderForm.itemPrice === 0" class="mt-2 text-sm text-green-600 dark:text-green-400">
                  🎁 Item Bonus (Gratis)
                </p>
              </div>

              <div>
                <label for="itemNote" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                  Catatan / Alasan <span class="text-xs text-zinc-400">(Opsional)</span>
                </label>
                <textarea
                  id="itemNote"
                  v-model="customOrderForm.itemNote"
                  placeholder="Contoh: Bonus Ultah, Pengganti Roti Gosong, dll."
                  rows="3"
                  class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-zinc-900 dark:text-white resize-none"
                ></textarea>
                <p class="mt-1 text-xs text-zinc-500">
                  Tambahkan catatan jika diperlukan (misal: alasan bonus atau permintaan khusus)
                </p>
              </div>
            </div>

            <div v-if="!customOrderForm.itemName || customOrderForm.itemPrice < 0" class="rounded-xl border border-yellow-200 dark:border-yellow-800/30 bg-yellow-50 dark:bg-yellow-900/10 p-4">
              <p class="text-sm text-yellow-800 dark:text-yellow-200">
                ⚠️ Pastikan nama item terisi dan harga tidak negatif (harga 0 diperbolehkan untuk bonus)
              </p>
            </div>
          </div>

          <div class="px-6 pb-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 shrink-0 bg-white dark:bg-zinc-900">
            <Button 
              class="w-full gap-2 h-14 text-lg font-bold shadow-xl shadow-orange-500/20" 
              :disabled="!customOrderForm.itemName || customOrderForm.itemPrice < 0"
              @click="addCustomOrderToCart"
            >
              <ShoppingCart class="h-5 w-5" /> Tambah ke Keranjang
            </Button>
          </div>
        </div>
      </div>
    </transition>
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
