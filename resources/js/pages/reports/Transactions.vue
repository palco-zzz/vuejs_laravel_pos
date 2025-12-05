<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Calendar, Download, Filter, X, Eye } from 'lucide-vue-next';

interface TransactionItem {
    name: string;
    quantity: number;
    price: number;
    subtotal: number;
    is_custom: boolean;
}

interface Transaction {
    id: number;
    order_number: string;
    date_time: string;
    branch_name: string;
    cashier_name: string;
    total: number;
    payment_method: string;
    status: string;
    items: TransactionItem[];
}

interface Branch {
    id: number;
    nama: string;
}

interface PaymentMethod {
    value: string;
    label: string;
}

const props = defineProps<{
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    summary: {
        total_revenue: number;
        total_count: number;
    };
    branches: Branch[];
    paymentMethods: PaymentMethod[];
    filters: {
        start_date: string;
        end_date: string;
        branch_id: number | null;
        payment_method: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Transaksi', href: '/reports/transactions' },
];

// Filter state
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedBranch = ref(props.filters.branch_id);
const selectedPaymentMethod = ref(props.filters.payment_method);

// Modal state
const selectedTransaction = ref<Transaction | null>(null);
const isDetailModalOpen = ref(false);

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const getPaymentMethodLabel = (value: string) => {
    const method = props.paymentMethods.find(m => m.value === value);
    return method ? method.label : value;
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/20';
        case 'pending':
            return 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/20';
        case 'cancelled':
            return 'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/20';
        default:
            return 'bg-zinc-100 dark:bg-zinc-500/10 text-zinc-700 dark:text-zinc-400 border-zinc-200 dark:border-zinc-500/20';
    }
};

const applyFilters = () => {
    router.get('/reports/transactions', {
        start_date: startDate.value,
        end_date: endDate.value,
        branch_id: selectedBranch.value,
        payment_method: selectedPaymentMethod.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    const today = new Date().toISOString().split('T')[0];
    startDate.value = today;
    endDate.value = today;
    selectedBranch.value = null;
    selectedPaymentMethod.value = null;
    applyFilters();
};

const openDetailModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedTransaction.value = null;
};
</script>

<template>
    <Head title="Laporan Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Transaksi</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Riwayat dan detail semua transaksi</p>
                </div>
                <Button variant="outline" class="gap-2">
                    <Download class="h-4 w-4" />
                    Export Excel
                </Button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-6 rounded-xl">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Total Pendapatan</p>
                    <h3 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ formatRupiah(summary.total_revenue) }}</h3>
                </div>
                <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-6 rounded-xl">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Total Transaksi</p>
                    <h3 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ summary.total_count }} <span class="text-base font-normal text-zinc-500">Transaksi</span></h3>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6">
                <div class="flex items-center gap-2 mb-4">
                    <Filter class="h-4 w-4 text-zinc-500" />
                    <h3 class="font-medium text-zinc-900 dark:text-white">Filter</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tanggal Mulai</label>
                        <Input v-model="startDate" type="date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tanggal Akhir</label>
                        <Input v-model="endDate" type="date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Cabang</label>
                        <select v-model="selectedBranch" class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm">
                            <option :value="null">Semua Cabang</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.nama }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Metode Pembayaran</label>
                        <select v-model="selectedPaymentMethod" class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm">
                            <option :value="null">Semua Metode</option>
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <Button @click="applyFilters" class="gap-2">
                        <Filter class="h-4 w-4" />
                        Terapkan Filter
                    </Button>
                    <Button @click="resetFilters" variant="outline" class="gap-2">
                        <X class="h-4 w-4" />
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">No. Transaksi</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Cabang</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Kasir</th>
                                <th class="px-6 py-4 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">Pembayaran</th>
                                <th class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="transaction in transactions.data" :key="transaction.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{{ transaction.order_number }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ transaction.date_time }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ transaction.branch_name }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ transaction.cashier_name }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-zinc-900 dark:text-white">{{ formatRupiah(transaction.total) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        {{ getPaymentMethodLabel(transaction.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="getStatusBadgeClass(transaction.status)" class="text-xs px-2 py-1 rounded-full border capitalize">
                                        {{ transaction.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="openDetailModal(transaction)" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300">
                                        <Eye class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-zinc-500">
                                    Tidak ada transaksi ditemukan
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Menampilkan {{ (transactions.current_page - 1) * transactions.per_page + 1 }} - {{ Math.min(transactions.current_page * transactions.per_page, transactions.total) }} dari {{ transactions.total }} transaksi
                    </p>
                    <div class="flex gap-2">
                        <Link :href="`/reports/transactions?page=${transactions.current_page - 1}`" v-if="transactions.current_page > 1" preserve-state preserve-scroll>
                            <Button variant="outline" size="sm">Previous</Button>
                        </Link>
                        <Link :href="`/reports/transactions?page=${transactions.current_page + 1}`" v-if="transactions.current_page < transactions.last_page" preserve-state preserve-scroll>
                            <Button variant="outline" size="sm">Next</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <transition name="fade">
            <div v-if="isDetailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeDetailModal"></div>
                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Detail Transaksi</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ selectedTransaction?.order_number }}</p>
                        </div>
                        <button @click="closeDetailModal" class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto flex-1">
                        <!-- Transaction Info -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Tanggal & Waktu</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ selectedTransaction?.date_time }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Cabang</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ selectedTransaction?.branch_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Kasir</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ selectedTransaction?.cashier_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Metode Pembayaran</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ getPaymentMethodLabel(selectedTransaction?.payment_method || '') }}</p>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="border-t border-zinc-200 dark:border-zinc-800 pt-6">
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-4">Item Pesanan</h4>
                            <div class="space-y-3">
                                <div v-for="(item, index) in selectedTransaction?.items" :key="index" class="flex justify-between items-start py-3 border-b border-zinc-100 dark:border-zinc-800/50 last:border-0">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ item.name }}
                                            <span v-if="item.is_custom" class="ml-2 text-xs px-2 py-0.5 rounded bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400">Custom</span>
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ item.quantity }} x {{ formatRupiah(item.price) }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ formatRupiah(item.subtotal) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="mt-6 pt-6 border-t-2 border-zinc-200 dark:border-zinc-800">
                            <div class="flex justify-between items-center">
                                <p class="text-base font-medium text-zinc-900 dark:text-white">Total Pembayaran</p>
                                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatRupiah(selectedTransaction?.total || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
