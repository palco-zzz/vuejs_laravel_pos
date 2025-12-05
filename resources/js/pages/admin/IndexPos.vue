<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import { ShoppingCart, Printer, PlusCircle, Edit3, Trash2, Plus, Minus, X, Search } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const toast = useToast();

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

interface CartItem {
    id: number;
    name: string;
    price: number;
    qty: number;
    icon: string;
    is_custom?: boolean;
}

interface OrderResponse {
    success: boolean;
    message: string;
    order: {
        id: number;
        order_number: string;
        subtotal: number;
        tax: number;
        total: number;
        created_at: string;
        items: Array<{
            item_name: string;
            price: number;
            quantity: number;
            subtotal: number;
        }>;
    };
}

const props = defineProps<{
    menus: MenuData[];
    categories: CategoryData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Point of Sales',
        href: '/pos',
    },
];

// State
const posCategory = ref<number | 'all'>('all');
const cart = ref<CartItem[]>([]);
const orderNumber = ref(Math.floor(Math.random() * 9000) + 1000);
const searchQuery = ref('');
const isProcessing = ref(false);

// Custom Order Modal
const showCustomOrderModal = ref(false);
const customOrderName = ref('');
const customOrderPrice = ref<number>(0);

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
    return subtotal.value * 0.1;
});

const grandTotal = computed(() => {
    return subtotal.value + tax.value;
});

// Format currency
const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

// Add to cart
const addToCart = (product: MenuData) => {
    const existingItem = cart.value.find(item => item.id === product.id && !item.is_custom);
    if (existingItem) {
        existingItem.qty++;
    } else {
        cart.value.push({
            id: product.id,
            name: product.nama,
            price: product.harga,
            qty: 1,
            icon: product.icon || '🍞',
            is_custom: false,
        });
    }
};

// Custom Order Modal Handlers
const openCustomOrderModal = () => {
    customOrderName.value = '';
    customOrderPrice.value = 0;
    showCustomOrderModal.value = true;
};

const closeCustomOrderModal = () => {
    showCustomOrderModal.value = false;
    customOrderName.value = '';
    customOrderPrice.value = 0;
};

const addCustomOrder = () => {
    if (!customOrderName.value.trim()) {
        toast.error('Error', 'Nama item harus diisi');
        return;
    }
    if (customOrderPrice.value <= 0) {
        toast.error('Error', 'Harga harus lebih dari 0');
        return;
    }

    // Generate unique ID for custom item (negative to avoid collision with menu IDs)
    const customId = -Date.now();

    cart.value.push({
        id: customId,
        name: customOrderName.value.trim(),
        price: customOrderPrice.value,
        qty: 1,
        icon: '✏️',
        is_custom: true,
    });

    toast.success('Berhasil', 'Custom order ditambahkan ke keranjang');
    closeCustomOrderModal();
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
};

// Process Order & Print
const processAndPrint = async () => {
    if (cart.value.length === 0) {
        toast.error('Error', 'Keranjang kosong');
        return;
    }

    isProcessing.value = true;

    try {
        const payload = {
            items: cart.value.map(item => ({
                id: item.is_custom ? null : item.id,
                name: item.name,
                price: item.price,
                qty: item.qty,
                is_custom: item.is_custom || false,
            })),
            subtotal: subtotal.value,
            tax: tax.value,
            total: grandTotal.value,
            payment_method: 'cash',
        };

        const response = await axios.post<OrderResponse>('/pos/order', payload);

        if (response.data.success) {
            toast.success('Berhasil', 'Pesanan berhasil disimpan');

            // Print receipt
            printReceipt(response.data.order);

            // Reset cart after successful order
            resetCart();

            // Refresh page to update stock
            router.reload({ only: ['menus'] });
        }
    } catch (error: any) {
        console.error('Order error:', error);
        toast.error('Gagal', error.response?.data?.message || 'Gagal menyimpan pesanan');
    } finally {
        isProcessing.value = false;
    }
};

// Print Receipt Function
const printReceipt = (order: OrderResponse['order']) => {
    const receiptWindow = window.open('', '_blank', 'width=300,height=600');
    if (!receiptWindow) {
        toast.error('Error', 'Popup diblokir. Izinkan popup untuk mencetak struk.');
        return;
    }

    const receiptDate = new Date(order.created_at).toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

    const receiptHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Struk - ${order.order_number}</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    width: 80mm;
                    padding: 10px;
                    background: white;
                }
                .header {
                    text-align: center;
                    border-bottom: 1px dashed #000;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                .header h1 {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .header p {
                    font-size: 10px;
                    color: #666;
                }
                .info {
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px dashed #000;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 3px;
                }
                .items {
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px dashed #000;
                }
                .item {
                    margin-bottom: 8px;
                }
                .item-name {
                    font-weight: bold;
                }
                .item-details {
                    display: flex;
                    justify-content: space-between;
                    padding-left: 10px;
                    color: #666;
                }
                .totals {
                    margin-bottom: 10px;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 3px;
                }
                .total-row.grand {
                    font-weight: bold;
                    font-size: 14px;
                    border-top: 1px solid #000;
                    padding-top: 5px;
                    margin-top: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 1px dashed #000;
                }
                .footer p {
                    font-size: 10px;
                    color: #666;
                }
                @media print {
                    body {
                        width: 80mm;
                    }
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ROTI BAKAR EPOK</h1>
                <p>BANDUNG</p>
                <p>Jl. Contoh Alamat No. 123</p>
                <p>Telp: 0812-3456-7890</p>
            </div>
            
            <div class="info">
                <div class="info-row">
                    <span>No. Order:</span>
                    <span>${order.order_number}</span>
                </div>
                <div class="info-row">
                    <span>Tanggal:</span>
                    <span>${receiptDate}</span>
                </div>
                <div class="info-row">
                    <span>Kasir:</span>
                    <span>Admin</span>
                </div>
            </div>
            
            <div class="items">
                ${order.items.map(item => `
                    <div class="item">
                        <div class="item-name">${item.item_name}</div>
                        <div class="item-details">
                            <span>${item.quantity} x ${formatRupiahPlain(item.price)}</span>
                            <span>${formatRupiahPlain(item.subtotal)}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>${formatRupiahPlain(order.subtotal)}</span>
                </div>
                <div class="total-row">
                    <span>Pajak (10%)</span>
                    <span>${formatRupiahPlain(order.tax)}</span>
                </div>
                <div class="total-row grand">
                    <span>TOTAL</span>
                    <span>${formatRupiahPlain(order.total)}</span>
                </div>
            </div>
            
            <div class="footer">
                <p>================================</p>
                <p>Terima Kasih</p>
                <p>Atas Kunjungan Anda</p>
                <p>================================</p>
            </div>
            
            <scr` + `ipt>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 500);
                }
            </scr` + `ipt>
        </bo` + `dy>
        </ht` + `ml>
    `;

    receiptWindow.document.write(receiptHTML);
    receiptWindow.document.close();
};

// Plain format for receipt (without Rp symbol formatting issues)
const formatRupiahPlain = (value: number) => {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
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
</script>

<template>

    <Head title="Point of Sales" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Main Content Wrapper -->
        <div class="h-full flex flex-col md:flex-row gap-6 fade-in">
            <!-- Product Grid -->
            <div class="flex-1 flex flex-col h-full overflow-hidden">
                <!-- Search Bar -->
                <div class="mb-4 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                    <Input v-model="searchQuery" type="text" placeholder="Cari menu..."
                        class="pl-9 w-full bg-white dark:bg-zinc-900" />
                </div>

                <!-- Category Filters -->
                <div class="flex gap-2 mb-6 overflow-x-auto pb-2 no-scrollbar">
                    <button @click="posCategory = 'all'"
                        :class="posCategory === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                        class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap shadow-sm border border-zinc-200 dark:border-zinc-800 transition-colors">
                        Semua Menu
                    </button>
                    <button v-for="cat in categories" :key="cat.id" @click="posCategory = cat.id"
                        :class="posCategory === cat.id ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                        class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-colors shadow-sm border border-zinc-200 dark:border-zinc-800">
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
                        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Custom Order</h4>
                        <span class="text-xs text-zinc-500 mt-1">Input item manual</span>
                    </div>

                    <!-- Product Cards -->
                    <div v-for="product in filteredPosProducts" :key="product.id" @click="addToCart(product)"
                        class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-4 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-800/40 cursor-pointer transition-all group shadow-sm dark:shadow-none flex flex-col h-full justify-between active:scale-95">
                        <div>
                            <div
                                class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-3 flex items-center justify-center relative overflow-hidden">
                                <div
                                    :class="`absolute inset-0 bg-gradient-to-tr ${getGradientColor(product.category_id)} to-transparent`">
                                </div>
                                <span class="text-4xl drop-shadow-sm relative z-10">{{ product.icon || '🍞' }}</span>
                            </div>
                            <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200 line-clamp-2">{{
                                product.nama }}</h4>
                            <p class="text-xs text-zinc-500 mt-1">{{ product.category?.nama }}</p>
                        </div>
                        <div
                            class="flex justify-between items-center mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <span class="text-xs text-zinc-500">Stok: {{ product.stok }}</span>
                            <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">{{
                                formatRupiah(product.harga) }}</span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredPosProducts.length === 0"
                        class="col-span-full flex flex-col items-center justify-center py-16">
                        <div
                            class="h-16 w-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                            <Search class="h-8 w-8 text-zinc-400" />
                        </div>
                        <p class="text-zinc-500">Tidak ada menu yang ditemukan</p>
                    </div>
                </div>
            </div>

            <!-- Cart Panel -->
            <div
                class="w-full md:w-80 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col h-[calc(100vh-10rem)] shadow-sm dark:shadow-none">
                <!-- Cart Header -->
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-zinc-200">Pesanan Baru</h3>
                        <span class="text-xs text-zinc-500">Order #{{ orderNumber }}</span>
                    </div>
                    <button v-if="cart.length > 0" @click="resetCart"
                        class="text-red-500 hover:text-red-700 text-xs font-medium">
                        Reset
                    </button>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                    <!-- Empty State -->
                    <div v-if="cart.length === 0"
                        class="h-full flex flex-col items-center justify-center text-zinc-400">
                        <ShoppingCart class="h-8 w-8 mb-2 opacity-50" />
                        <span class="text-xs">Keranjang kosong</span>
                        <span class="text-xs mt-1">Klik menu untuk menambahkan</span>
                    </div>

                    <!-- Cart Item -->
                    <div v-for="(item, index) in cart" :key="`${item.id}-${item.is_custom}`"
                        class="flex justify-between items-start group bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg">
                        <div class="flex gap-3 flex-1 min-w-0">
                            <div
                                class="h-10 w-10 bg-white dark:bg-zinc-800 rounded flex items-center justify-center text-lg border border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                                {{ item.icon }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-900 dark:text-zinc-200 font-medium line-clamp-1">
                                    {{ item.name }}
                                    <span v-if="item.is_custom" class="text-xs text-orange-500 ml-1">(Custom)</span>
                                </p>
                                <p class="text-xs text-zinc-500">{{ formatRupiah(item.price) }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ formatRupiah(item.price *
                                item.qty) }}</p>
                            <div class="flex items-center gap-1">
                                <button @click.stop="updateQty(index, -1)"
                                    class="h-6 w-6 rounded bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600 flex items-center justify-center transition-colors">
                                    <Minus class="h-3 w-3" />
                                </button>
                                <span class="w-6 text-center text-sm font-medium">{{ item.qty }}</span>
                                <button @click.stop="updateQty(index, 1)"
                                    class="h-6 w-6 rounded bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600 flex items-center justify-center transition-colors">
                                    <Plus class="h-3 w-3" />
                                </button>
                                <button @click.stop="removeFromCart(index)"
                                    class="h-6 w-6 rounded bg-red-100 dark:bg-red-500/20 hover:bg-red-200 dark:hover:bg-red-500/30 flex items-center justify-center ml-1 transition-colors">
                                    <Trash2 class="h-3 w-3 text-red-500" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Footer -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 rounded-b-xl">
                    <!-- Add Custom Order Button -->
                    <button @click="openCustomOrderModal"
                        class="w-full mb-4 bg-white dark:bg-zinc-800 border border-dashed border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-500 dark:text-zinc-300 text-xs font-medium py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <PlusCircle class="h-3 w-3" /> Tambah Custom Order
                    </button>

                    <!-- Totals -->
                    <div class="flex justify-between mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                        <span>Subtotal</span>
                        <span>{{ formatRupiah(subtotal) }}</span>
                    </div>
                    <div class="flex justify-between mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                        <span>Pajak (10%)</span>
                        <span>{{ formatRupiah(tax) }}</span>
                    </div>
                    <div class="flex justify-between mb-6 text-base font-semibold text-zinc-900 dark:text-white">
                        <span>Total</span>
                        <span class="text-orange-600 dark:text-orange-400">{{ formatRupiah(grandTotal) }}</span>
                    </div>

                    <!-- Process Button -->
                    <button @click="processAndPrint" :disabled="cart.length === 0 || isProcessing"
                        :class="cart.length === 0 || isProcessing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-800 dark:hover:bg-zinc-200'"
                        class="w-full bg-zinc-900 dark:bg-white text-white dark:text-zinc-950 font-medium py-3 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                        <Spinner v-if="isProcessing" class="h-4 w-4" />
                        <Printer v-else class="h-4 w-4" />
                        {{ isProcessing ? 'Memproses...' : 'Proses & Cetak' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Order Modal -->
        <Dialog :open="showCustomOrderModal" @update:open="showCustomOrderModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Custom Order</DialogTitle>
                    <DialogDescription>
                        Tambahkan item custom ke pesanan
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="custom-name">Nama Item</Label>
                        <Input id="custom-name" v-model="customOrderName" type="text"
                            placeholder="Contoh: Roti Bakar Spesial" />
                    </div>
                    <div class="space-y-2">
                        <Label for="custom-price">Harga (Rp)</Label>
                        <Input id="custom-price" v-model.number="customOrderPrice" type="number" min="0"
                            placeholder="25000" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCustomOrderModal">
                        Batal
                    </Button>
                    <Button type="button" @click="addCustomOrder">
                        <Plus class="h-4 w-4 mr-2" />
                        Tambah ke Keranjang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
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
</style>