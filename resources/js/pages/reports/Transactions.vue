<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import GlassCard from '@/components/ui/GlassCard.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Calendar, Download, Filter, X, Eye, AlertCircle } from 'lucide-vue-next';

interface TransactionItem {
    name: string;
    quantity: number;
    price: number;
    subtotal: number;
    is_custom: boolean;
    note?: string | null;
}

interface Transaction {
    id: number;
    order_number: string;
    date_time: string;
    branch_name: string;
    cashier_name: string;
    creator_role?: string | null;  // For "BANTUAN ADMIN" badge
    // Void/Delete info
    deleted_at?: string | null;
    deleted_by?: number | null;
    delete_reason?: string | null;
    deleter_name?: string | null;
    // Edit info
    edited_at?: string | null;
    edited_by?: number | null;
    edit_reason?: string | null;
    edited_by_name?: string | null;
    // Transaction details
    total: number;
    payment_method: string;
    status: string;
    notes?: string;
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
const isEditModalOpen = ref(false);

// Edit form state
const editForm = ref({
    payment_method: '',
    status: '',
    notes: '',
});

// Void modal state
const isVoidModalOpen = ref(false);
const voidReason = ref('');

// Get current user from page props
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

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

const openEditModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    editForm.value = {
        payment_method: transaction.payment_method,
        status: transaction.status,
        notes: transaction.notes || '',
    };
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedTransaction.value = null;
    editForm.value = {
        payment_method: '',
        status: '',
        notes: '',
    };
};

const saveTransaction = () => {
    if (!selectedTransaction.value) return;

    // Show warning for void/cancellation
    if ((editForm.value.status === 'cancelled' || editForm.value.status === 'refunded') &&
        selectedTransaction.value.status !== editForm.value.status) {
        const confirmed = confirm(
            `Apakah Anda yakin ingin mengubah status transaksi menjadi "${editForm.value.status}"? Tindakan ini akan membatalkan transaksi.`
        );

        if (!confirmed) return;
    }

    router.put(
        `/reports/transactions/${selectedTransaction.value.id}`,
        editForm.value,
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
            },
        }
    );
};

// Void Transaction Functions
const openVoidModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    voidReason.value = '';
    isVoidModalOpen.value = true;
};

const closeVoidModal = () => {
    isVoidModalOpen.value = false;
    selectedTransaction.value = null;
    voidReason.value = '';
};

const confirmVoid = async () => {
    if (!selectedTransaction.value) return;

    if (!voidReason.value.trim()) {
        alert('Alasan pembatalan wajib diisi');
        return;
    }

    try {
        const response = await fetch(`/pos/order/${selectedTransaction.value.id}/void`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                delete_reason: voidReason.value
            }),
        });

        const result = await response.json();

        if (result.success) {
            alert('Transaksi berhasil dibatalkan');
            closeVoidModal();
            router.reload({ only: ['transactions'] });
        } else {
            alert('Gagal: ' + result.message);
        }
    } catch (error) {
        console.error('Error voiding transaction:', error);
        alert('Terjadi kesalahan saat membatalkan transaksi');
    }
};

// Export to Excel function
const exportToExcel = () => {
    // Build the export URL with current filter parameters
    const params = new URLSearchParams();

    if (startDate.value) {
        params.append('start_date', startDate.value);
    }
    if (endDate.value) {
        params.append('end_date', endDate.value);
    }
    if (selectedBranch.value) {
        params.append('branch_id', selectedBranch.value.toString());
    }
    if (selectedPaymentMethod.value) {
        params.append('payment_method', selectedPaymentMethod.value);
    }

    // Navigate to the export URL to trigger download
    const exportUrl = `/reports/transactions/export?${params.toString()}`;
    window.location.href = exportUrl;
};
</script>

<template>

    <Head title="Laporan Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between md:items-end gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Laporan Transaksi</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Riwayat dan detail semua transaksi</p>
                </div>
                <button @click="exportToExcel"
                    class="gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium">
                    <Download class="h-4 w-4" />
                    Export Excel
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative overflow-hidden rounded-[2rem] p-6
                            bg-white/60 dark:bg-slate-900/70 backdrop-blur-xl
                            border border-white/40 dark:border-slate-700/50
                            shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-white/30 to-transparent dark:from-slate-800/20 dark:to-slate-900/10 pointer-events-none" />
                    <div class="relative z-10">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Total Pendapatan</p>
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white">{{
                            formatRupiah(summary.total_revenue) }}</h3>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-[2rem] p-6
                            bg-white/60 dark:bg-slate-900/70 backdrop-blur-xl
                            border border-white/40 dark:border-slate-700/50
                            shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-white/30 to-transparent dark:from-slate-800/20 dark:to-slate-900/10 pointer-events-none" />
                    <div class="relative z-10">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Total Transaksi</p>
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white">{{ summary.total_count }} <span
                                class="text-base font-normal text-slate-500">Transaksi</span></h3>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <GlassCard class="space-y-4">
                <div class="flex items-center gap-2">
                    <Filter class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                    <h3 class="font-bold text-slate-900 dark:text-white">Filter</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tanggal
                            Mulai</label>
                        <input v-model="startDate" type="date"
                            class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 text-slate-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tanggal
                            Akhir</label>
                        <input v-model="endDate" type="date"
                            class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 text-slate-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cabang</label>
                        <select v-model="selectedBranch"
                            class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 text-slate-900 dark:text-white">
                            <option :value="null">Semua Cabang</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.nama }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Metode
                            Pembayaran</label>
                        <select v-model="selectedPaymentMethod"
                            class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 text-slate-900 dark:text-white">
                            <option :value="null">Semua Metode</option>
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{
                                method.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="applyFilters"
                        class="gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium">
                        <Filter class="h-4 w-4" />
                        Terapkan Filter
                    </button>
                    <button @click="resetFilters"
                        class="gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center justify-center font-medium">
                        <X class="h-4 w-4" />
                        Reset
                    </button>
                </div>
            </GlassCard>

            <!-- Transactions Table -->
            <GlassCard noPadding class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                            <tr class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">
                                <th class="p-5">No. Transaksi</th>
                                <th class="p-5">Tanggal & Waktu</th>
                                <th class="p-5">Cabang</th>
                                <th class="p-5">Kasir</th>
                                <th class="p-5 text-right">Total</th>
                                <th class="p-5 text-center">Pembayaran</th>
                                <th class="p-5 text-center">Status</th>
                                <th class="p-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="transaction in transactions.data" :key="transaction.id" :class="[
                                'transition-colors',
                                transaction.deleted_at
                                    ? 'opacity-50 bg-red-50/50 dark:bg-red-500/5'
                                    : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                            ]">
                                <td class="p-5">
                                    <div class="flex items-center gap-2">
                                        <span :class="[
                                            'font-bold text-slate-900 dark:text-white',
                                            transaction.deleted_at ? 'line-through' : ''
                                        ]">{{ transaction.order_number }}</span>
                                        <span v-if="transaction.deleted_at"
                                            class="text-[10px] px-1.5 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 font-bold">
                                            VOID
                                        </span>
                                        <span v-if="transaction.creator_role === 'admin' && !transaction.deleted_at"
                                            class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 font-bold">
                                            PUSAT
                                        </span>
                                    </div>
                                </td>
                                <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ transaction.date_time }}
                                </td>
                                <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ transaction.branch_name }}
                                </td>
                                <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ transaction.cashier_name
                                    }}</td>
                                <td class="p-5 text-right font-bold text-slate-900 dark:text-white">{{
                                    formatRupiah(transaction.total) }}</td>
                                <td class="p-5 text-center">
                                    <span
                                        class="text-xs px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                                        {{ getPaymentMethodLabel(transaction.payment_method) }}
                                    </span>
                                </td>
                                <td class="p-5 text-center">
                                    <span :class="getStatusBadgeClass(transaction.status)"
                                        class="text-xs px-3 py-1.5 rounded-lg border capitalize font-bold">
                                        {{ transaction.status }}
                                    </span>
                                </td>
                                <td class="p-5 text-center">
                                    <button
                                        class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center mx-auto transition-colors"
                                        @click="openDetailModal(transaction)">
                                        <Eye class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="p-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                                            <Calendar class="h-7 w-7 text-slate-400" />
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400 font-medium">Tidak ada transaksi
                                            ditemukan</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.last_page > 1"
                    class="p-5 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Menampilkan {{ (transactions.current_page - 1) * transactions.per_page + 1 }} - {{
                            Math.min(transactions.current_page * transactions.per_page, transactions.total) }} dari {{
                            transactions.total }} transaksi
                    </p>
                    <div class="flex gap-2">
                        <Link :href="`/reports/transactions?page=${transactions.current_page - 1}`"
                            v-if="transactions.current_page > 1" preserve-state preserve-scroll>
                            <button
                                class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Previous</button>
                        </Link>
                        <Link :href="`/reports/transactions?page=${transactions.current_page + 1}`"
                            v-if="transactions.current_page < transactions.last_page" preserve-state preserve-scroll>
                            <button
                                class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Next</button>
                        </Link>
                    </div>
                </div>
            </GlassCard>
        </div>

        <!-- Detail Modal -->
        <transition name="fade">
            <div v-if="isDetailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeDetailModal"></div>
                <div
                    class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Detail Transaksi</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{
                                selectedTransaction?.order_number }}</p>
                        </div>
                        <button @click="closeDetailModal"
                            class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto flex-1">
                        <!-- Void/Cancellation Alert -->
                        <div v-if="selectedTransaction?.deleted_at"
                            class="mb-6 bg-red-50 dark:bg-red-500/10 border-l-4 border-red-500 dark:border-red-400 p-4 rounded-r-lg">
                            <div class="flex items-start gap-3">
                                <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <h4 class="font-bold text-red-900 dark:text-red-200 mb-1">⛔ Transaksi Dibatalkan
                                    </h4>
                                    <p class="text-sm text-red-800 dark:text-red-300 mb-1">
                                        Dibatalkan oleh <span class="font-semibold">{{ selectedTransaction?.deleter_name
                                            || 'Admin' }}</span>
                                        pada {{ selectedTransaction?.deleted_at }}.
                                    </p>
                                    <p class="text-sm text-red-800 dark:text-red-300">
                                        <span class="font-bold">Alasan:</span> {{ selectedTransaction?.delete_reason ||
                                        '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Alert -->
                        <div v-if="selectedTransaction?.edited_at && !selectedTransaction?.deleted_at"
                            class="mb-6 bg-yellow-50 dark:bg-yellow-500/10 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 rounded-r-lg">
                            <div class="flex items-start gap-3">
                                <AlertCircle
                                    class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <h4 class="font-bold text-yellow-900 dark:text-yellow-200 mb-1">⚠️ Transaksi Telah
                                        Diedit</h4>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-1">
                                        Diedit oleh <span class="font-semibold">{{ selectedTransaction?.edited_by_name
                                            || 'Admin' }}</span>
                                        pada {{ selectedTransaction?.edited_at }}.
                                    </p>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                        <span class="font-bold">Alasan:</span> {{ selectedTransaction?.edit_reason ||
                                        '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Info -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Tanggal & Waktu</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{
                                    selectedTransaction?.date_time }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Cabang</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{
                                    selectedTransaction?.branch_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Kasir</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{
                                    selectedTransaction?.cashier_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Metode Pembayaran</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{
                                    getPaymentMethodLabel(selectedTransaction?.payment_method || '') }}</p>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="border-t border-zinc-200 dark:border-zinc-800 pt-6">
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-4">Item Pesanan</h4>
                            <div class="space-y-3">
                                <div v-for="(item, index) in selectedTransaction?.items" :key="index"
                                    class="flex justify-between items-start py-3 border-b border-zinc-100 dark:border-zinc-800/50 last:border-0">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ item.name }}
                                            <span v-if="item.is_custom"
                                                class="ml-2 text-xs px-2 py-0.5 rounded bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400">Custom</span>
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ item.quantity }} x
                                            {{ formatRupiah(item.price) }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{
                                        formatRupiah(item.subtotal) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="mt-6 pt-6 border-t-2 border-zinc-200 dark:border-zinc-800">
                            <div class="flex justify-between items-center">
                                <p class="text-base font-medium text-zinc-900 dark:text-white">Total Pembayaran</p>
                                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{
                                    formatRupiah(selectedTransaction?.total || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Edit Transaction Modal (Admin Only) -->
        <transition name="fade">
            <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeEditModal"></div>
                <div
                    class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Edit Transaksi</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{
                                selectedTransaction?.order_number }}</p>
                        </div>
                        <button @click="closeEditModal"
                            class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto flex-1">
                        <div class="space-y-4">
                            <!-- Payment Method -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Metode Pembayaran
                                </label>
                                <select v-model="editForm.payment_method"
                                    class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                        {{ method.label }}</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Status
                                </label>
                                <select v-model="editForm.status"
                                    class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                                <p v-if="editForm.status === 'cancelled' || editForm.status === 'refunded'"
                                    class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    ⚠️ Peringatan: Status ini akan membatalkan/void transaksi!
                                </p>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Catatan Koreksi (Opsional)
                                </label>
                                <textarea v-model="editForm.notes" rows="3"
                                    class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                    placeholder="Alasan koreksi transaksi..."></textarea>
                            </div>

                            <!-- Current Transaction Info -->
                            <div
                                class="bg-zinc-50 dark:bg-zinc-900/60 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                <h4 class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2">Informasi
                                    Transaksi</h4>
                                <div class="text-sm space-y-1">
                                    <p class="text-zinc-700 dark:text-zinc-300"><span class="font-medium">Total:</span>
                                        {{ formatRupiah(selectedTransaction?.total || 0) }}</p>
                                    <p class="text-zinc-700 dark:text-zinc-300"><span class="font-medium">Kasir:</span>
                                        {{ selectedTransaction?.cashier_name }}</p>
                                    <p class="text-zinc-700 dark:text-zinc-300"><span
                                            class="font-medium">Tanggal:</span> {{ selectedTransaction?.date_time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3">
                        <Button @click="closeEditModal" variant="outline">
                            Batal
                        </Button>
                        <Button @click="saveTransaction" class="bg-orange-600 hover:bg-orange-700 text-white">
                            Simpan Perubahan
                        </Button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Void Transaction Modal (Admin Only) -->
        <transition name="fade">
            <div v-if="isVoidModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeVoidModal"></div>

                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full mx-4">
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Batalkan Transaksi</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{
                                selectedTransaction?.order_number }}</p>
                        </div>
                        <button @click="closeVoidModal"
                            class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        <!-- Warning -->
                        <div
                            class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg p-4 mb-6">
                            <div class="flex gap-3">
                                <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="font-medium text-red-900 dark:text-red-200 text-sm mb-1">Peringatan</h4>
                                    <p class="text-xs text-red-700 dark:text-red-300">
                                        Tindakan ini akan membatalkan transaksi secara permanen.
                                        Transaksi yang sudah dibatalkan tidak dapat dikembalikan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Reason Input -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Alasan Pembatalan <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="voidReason" rows="4"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Contoh: Kesalahan input, pelanggan membatalkan, duplikat transaksi, dll."
                                required></textarea>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3">
                        <Button @click="closeVoidModal" variant="outline">
                            Batal
                        </Button>
                        <Button @click="confirmVoid" class="bg-red-600 hover:bg-red-700 text-white">
                            Batalkan Transaksi
                        </Button>
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
