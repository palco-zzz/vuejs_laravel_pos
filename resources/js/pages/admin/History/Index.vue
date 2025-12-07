<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Printer, Eye, X, Clock, Receipt, Pencil, AlertCircle, Trash2, Plus, Ban } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

interface TransactionItem {
    id: number;
    name: string;
    quantity: number;
    price: number;
    subtotal: number;
    is_custom: boolean;
}

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

interface Menu {
    id: number;
    nama: string;
    harga: number;
    category_name: string;
}

const props = defineProps<{
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    menus: Menu[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Riwayat Transaksi', href: '/pos/history' },
];

// Receipt Modal
const selectedTransaction = ref<Transaction | null>(null);
const isReceiptModalOpen = ref(false);

// Edit Modal
const isEditModalOpen = ref(false);
const editItems = ref<Array<{ id: number | null, menu_id: number | null, quantity: number, price: number }>>([]);
const editReason = ref('');

// Void Modal
const isVoidModalOpen = ref(false);
const voidReason = ref('');

// Status Filter
const statusFilter = ref<string>('all');

// Get current user
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

// Initialize toast
const toast = useToast();

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const getPaymentMethodLabel = (method: string) => {
    const methods: Record<string, string> = {
        cash: 'Tunai',
        bca_va: 'BCA VA',
        bri_va: 'BRI VA',
        gopay: 'GoPay',
        ovo: 'OVO',
        transfer: 'Transfer',
        qris: 'QRIS',
    };
    return methods[method] || method;
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'success':
        case 'completed':
            return 'bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-400';
        case 'pending':
            return 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400';
        case 'cancelled':
        case 'refunded':
            return 'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400';
        case 'failed':
            return 'bg-zinc-100 dark:bg-zinc-500/10 text-zinc-700 dark:text-zinc-400';
        default:
            return 'bg-zinc-100 dark:bg-zinc-500/10 text-zinc-700 dark:text-zinc-400';
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        success: 'Sukses',
        completed: 'Sukses',
        pending: 'Pending',
        cancelled: 'Dibatalkan',
        refunded: 'Dikembalikan',
        failed: 'Gagal',
    };
    return labels[status] || status;
};

const openReceiptModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    isReceiptModalOpen.value = true;
};

const closeReceiptModal = () => {
    isReceiptModalOpen.value = false;
    selectedTransaction.value = null;
};

const printReceipt = () => {
    if (!selectedTransaction.value) return;

    const receiptId = `receipt-${selectedTransaction.value.id}`;
    const receiptElement = document.getElementById(receiptId);

    if (!receiptElement) {
        console.error('Receipt element not found');
        return;
    }

    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=800,height=600');

    if (!printWindow) {
        alert('Popup blocker is preventing the print window. Please allow popups for this site.');
        return;
    }

    // Get all stylesheets from the current document
    const styles = Array.from(document.styleSheets)
        .map(styleSheet => {
            try {
                return Array.from(styleSheet.cssRules)
                    .map(rule => rule.cssText)
                    .join('\n');
            } catch (e) {
                // Some stylesheets may not be accessible due to CORS
                return '';
            }
        })
        .join('\n');

    // Write the HTML content to the new window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Struk Pembayaran - ${selectedTransaction.value.order_number}</title>
            <meta charset="utf-8">
            <style>
                ${styles}
                
                /* Additional print-specific styles */
                @media print {
                    @page {
                        margin: 0;
                        size: 80mm auto; /* Thermal printer width */
                    }
                    body {
                        margin: 0;
                        padding: 10mm;
                        font-family: 'Courier New', monospace;
                    }
                }
                
                body {
                    font-family: 'Courier New', monospace;
                    max-width: 80mm;
                    margin: 0 auto;
                    padding: 10px;
                }
            </style>
        </head>
        <body>
            ${receiptElement.innerHTML}
        </body>
        </html>
    `);

    printWindow.document.close();

    // Wait for content to load, then print
    printWindow.onload = () => {
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }, 250);
    };
};

const openEditModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    editItems.value = transaction.items.map(item => ({
        id: item.id,
        menu_id: props.menus.find(m => m.nama === item.name)?.id || null,
        quantity: item.quantity,
        price: item.price,
    }));
    editReason.value = '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedTransaction.value = null;
    editItems.value = [];
    editReason.value = '';
};

const calculateNewTotal = computed(() => {
    if (!selectedTransaction.value) return 0;

    let subtotal = 0;
    editItems.value.forEach((editItem) => {
        const price = editItem.price;
        const qty = editItem.quantity;
        subtotal += price * qty;
    });

    const tax = subtotal * 0.10; // 10% tax
    return subtotal + tax;
});

const handleMenuChange = (index: number, menuId: number) => {
    const menu = props.menus.find(m => m.id === menuId);
    if (menu) {
        editItems.value[index].menu_id = menu.id;
        editItems.value[index].price = menu.harga;
    }
};

const deleteItem = (index: number) => {
    if (editItems.value.length <= 1) {
        alert('Order harus memiliki minimal 1 item');
        return;
    }

    const confirmed = confirm('Apakah Anda yakin ingin menghapus item ini?');
    if (confirmed) {
        editItems.value.splice(index, 1);
    }
};

const addNewItem = () => {
    if (props.menus.length === 0) {
        alert('Tidak ada menu tersedia');
        return;
    }

    // Add new item with first menu as default
    const firstMenu = props.menus[0];
    editItems.value.push({
        id: null,  // null means new item
        menu_id: firstMenu.id,
        quantity: 1,
        price: firstMenu.harga,
    });
};

const saveEditedItems = () => {
    if (!selectedTransaction.value) return;
    if (!editReason.value.trim()) {
        alert('Mohon isi alasan perubahan');
        return;
    }

    router.put(
        `/pos/order/${selectedTransaction.value.id}/items`,
        {
            items: editItems.value,
            edit_reason: editReason.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
                toast.success('Berhasil!', 'Transaksi berhasil diperbarui');
            },
            onError: (errors) => {
                toast.error('Gagal!', 'Terjadi kesalahan saat memperbarui transaksi');
            },
        }
    );
};

// Void Transaction
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

const confirmVoidTransaction = () => {
    if (!selectedTransaction.value) return;
    if (!voidReason.value.trim() || voidReason.value.trim().length < 10) {
        toast.error('Gagal!', 'Alasan pembatalan minimal 10 karakter');
        return;
    }

    router.put(
        `/transactions/${selectedTransaction.value.id}/void`,
        {
            delete_reason: voidReason.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeVoidModal();
                toast.success('Berhasil!', 'Transaksi berhasil dibatalkan');
            },
            onError: () => {
                toast.error('Gagal!', 'Terjadi kesalahan saat membatalkan transaksi');
            },
        }
    );
};

// Delete Pending Order
const deletePendingOrder = (transaction: Transaction) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus order ${transaction.order_number}?`)) {
        return;
    }

    router.delete(`/transactions/${transaction.id}/pending`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', 'Order pending berhasil dihapus');
        },
        onError: () => {
            toast.error('Gagal!', 'Terjadi kesalahan saat menghapus order');
        },
    });
};

// Check if transaction is from today
const isToday = (dateString: string) => {
    const today = new Date();
    const [day, month, year] = dateString.split('/');
    const transactionDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));

    return today.getDate() === transactionDate.getDate() &&
        today.getMonth() === transactionDate.getMonth() &&
        today.getFullYear() === transactionDate.getFullYear();
};

// Check if user can void transaction (cashier only for today's transactions)
const canVoidTransaction = (transaction: Transaction) => {
    if (transaction.status !== 'success' && transaction.status !== 'completed') return false;

    // Admin can void any success transaction
    if (currentUser.value.role === 'admin') return true;

    // Cashier can only void today's transactions
    return currentUser.value.role === 'cashier' && isToday(transaction.date);
};

// Filtered transactions
const filteredTransactions = computed(() => {
    if (statusFilter.value === 'all') {
        return props.transactions.data;
    }
    return props.transactions.data.filter(t => {
        const status = t.status === 'completed' ? 'success' : t.status;
        return status === statusFilter.value;
    });
});
</script>

<template>

    <Head title="Riwayat Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 bg-orange-100 dark:bg-orange-500/10 rounded-lg flex items-center justify-center">
                        <Clock class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Riwayat Transaksi</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Daftar semua transaksi yang telah Anda buat
                        </p>
                    </div>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-4">
                <div class="flex gap-2 overflow-x-auto">
                    <button @click="statusFilter = 'all'" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                        statusFilter === 'all'
                            ? 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400'
                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                    ]">
                        Semua
                    </button>
                    <button @click="statusFilter = 'pending'" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                        statusFilter === 'pending'
                            ? 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400'
                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                    ]">
                        Pending
                    </button>
                    <button @click="statusFilter = 'success'" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                        statusFilter === 'success'
                            ? 'bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-400'
                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                    ]">
                        Sukses
                    </button>
                    <button @click="statusFilter = 'cancelled'" :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                        statusFilter === 'cancelled'
                            ? 'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400'
                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                    ]">
                        Dibatalkan
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div
                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    No. Order</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Total</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Pembayaran</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="transaction in filteredTransactions" :key="transaction.id" :class="[
                                'transition-colors',
                                transaction.deleted_at
                                    ? 'opacity-60 bg-red-50 dark:bg-red-500/5'
                                    : 'hover:bg-zinc-50 dark:hover:bg-zinc-900/40'
                            ]">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div :class="[
                                            'h-10 w-10 rounded-lg flex items-center justify-center',
                                            transaction.deleted_at
                                                ? 'bg-red-100 dark:bg-red-500/20'
                                                : 'bg-zinc-100 dark:bg-zinc-800'
                                        ]">
                                            <Receipt :class="[
                                                'h-4 w-4',
                                                transaction.deleted_at ? 'text-red-500' : 'text-zinc-500'
                                            ]" />
                                        </div>
                                        <div>
                                            <p :class="[
                                                'text-sm font-medium',
                                                transaction.deleted_at
                                                    ? 'text-red-700 dark:text-red-400 line-through'
                                                    : 'text-zinc-900 dark:text-white'
                                            ]">{{ transaction.order_number }}</p>
                                            <p class="text-xs text-zinc-500">{{ transaction.items.length }} item</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-zinc-900 dark:text-white">{{ transaction.date }}</p>
                                    <p class="text-xs text-zinc-500">{{ transaction.time }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span :class="[
                                        'text-sm font-semibold',
                                        transaction.deleted_at
                                            ? 'text-red-600 dark:text-red-400 line-through'
                                            : 'text-zinc-900 dark:text-white'
                                    ]">{{ formatRupiah(transaction.total) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-xs px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        {{ getPaymentMethodLabel(transaction.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                        <!-- VOIDED/DIBATALKAN badge - highest priority -->
                                        <span v-if="transaction.deleted_at"
                                            :title="`Dibatalkan oleh ${transaction.deleter_name || 'Admin'} pada ${transaction.deleted_at}\nAlasan: ${transaction.delete_reason || '-'}`"
                                            class="text-xs px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 font-medium cursor-help">
                                            DIBATALKAN
                                        </span>
                                        <!-- Normal status if not deleted -->
                                        <span v-else :class="getStatusBadgeClass(transaction.status)"
                                            class="text-xs px-2.5 py-1 rounded-full">
                                            {{ getStatusLabel(transaction.status) }}
                                        </span>
                                        <!-- "BANTUAN PUSAT" badge if created by admin (only show if not deleted) -->
                                        <span v-if="transaction.user.role === 'admin' && !transaction.deleted_at"
                                            title="Transaksi ini dibuat oleh Admin"
                                            class="text-xs px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 cursor-help">
                                            BANTUAN PUSAT
                                        </span>
                                        <!-- "DIEDIT" badge if edited (only show if not deleted) -->
                                        <span v-if="transaction.edited_at && !transaction.deleted_at"
                                            :title="`Diedit oleh ${transaction.editor_name} pada ${transaction.edited_at}\nAlasan: ${transaction.edit_reason}`"
                                            class="text-xs px-2.5 py-1 rounded-full bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 cursor-help">
                                            DIEDIT
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Detail/View Button -->
                                        <Button variant="ghost" size="sm"
                                            class="gap-1.5 text-zinc-600 dark:text-zinc-400 hover:text-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                            @click="openReceiptModal(transaction)">
                                            <Eye class="h-4 w-4" />
                                            <span class="hidden sm:inline">Detail</span>
                                        </Button>

                                        <!-- Print Receipt Button -->
                                        <Button variant="ghost" size="sm"
                                            class="gap-1.5 text-orange-600 dark:text-orange-400 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-500/10"
                                            @click="openReceiptModal(transaction)">
                                            <Printer class="h-4 w-4" />
                                            <span class="hidden sm:inline">Print Struk</span>
                                        </Button>

                                        <!-- Admin-only: Edit Items (hide for deleted transactions) -->
                                        <Button
                                            v-if="currentUser.role === 'admin' && !transaction.deleted_at && (transaction.status === 'success' || transaction.status === 'completed')"
                                            variant="ghost" size="sm"
                                            class="gap-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-500/10"
                                            @click="openEditModal(transaction)">
                                            <Pencil class="h-4 w-4" />
                                            <span class="hidden sm:inline">Edit Items</span>
                                        </Button>

                                        <!-- Delete Pending Order (hide for deleted transactions) -->
                                        <Button v-if="transaction.status === 'pending' && !transaction.deleted_at"
                                            variant="ghost" size="sm"
                                            class="gap-1.5 text-red-600 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-500/10"
                                            @click="deletePendingOrder(transaction)">
                                            <Trash2 class="h-4 w-4" />
                                            <span class="hidden sm:inline">Hapus</span>
                                        </Button>

                                        <!-- Void/Cancel Transaction (hide for already deleted) -->
                                        <Button v-if="canVoidTransaction(transaction) && !transaction.deleted_at"
                                            variant="ghost" size="sm"
                                            class="gap-1.5 text-red-600 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-500/10"
                                            @click="openVoidModal(transaction)">
                                            <Ban class="h-4 w-4" />
                                            <span class="hidden sm:inline">Batalkan</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredTransactions.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
                                            <Receipt class="h-8 w-8 text-zinc-400" />
                                        </div>
                                        <p class="text-zinc-500 dark:text-zinc-400 mb-2">Belum ada transaksi</p>
                                        <Link href="/pos">
                                            <Button variant="outline" size="sm">Buat Transaksi Baru</Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.last_page > 1"
                    class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Menampilkan {{ (transactions.current_page - 1) * transactions.per_page + 1 }} -
                        {{ Math.min(transactions.current_page * transactions.per_page, transactions.total) }}
                        dari {{ transactions.total }} transaksi
                    </p>
                    <div class="flex gap-1">
                        <template v-for="(link, index) in transactions.links" :key="index">
                            <Link v-if="link.url" :href="link.url" preserve-state preserve-scroll :class="[
                                'px-3 py-1.5 text-sm rounded-md transition-colors',
                                link.active
                                    ? 'bg-orange-600 text-white'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'
                            ]" v-html="link.label" />
                            <span v-else
                                class="px-3 py-1.5 text-sm rounded-md bg-zinc-50 dark:bg-zinc-900 text-zinc-400 cursor-not-allowed"
                                v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Modal -->
        <transition name="fade">
            <div v-if="isReceiptModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeReceiptModal"></div>

                <div
                    class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-md mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Struk Transaksi</h3>
                        <button @click="closeReceiptModal"
                            class="h-10 w-10 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Receipt Content -->
                    <div :id="`receipt-${selectedTransaction?.id}`"
                        class="p-6 overflow-y-auto flex-1 print:overflow-visible">
                        <!-- Void/Cancellation Alert (Show if deleted) -->
                        <div v-if="selectedTransaction?.deleted_at"
                            class="mb-6 bg-red-50 dark:bg-red-500/10 border-l-4 border-red-500 dark:border-red-400 p-4 rounded-r-lg print:hidden">
                            <div class="flex items-start gap-3">
                                <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <h4 class="font-bold text-red-900 dark:text-red-200 mb-1">⛔ Transaksi Dibatalkan
                                    </h4>
                                    <p class="text-sm text-red-800 dark:text-red-300 mb-1">
                                        Dibatalkan oleh <span class="font-semibold">{{ selectedTransaction?.deleter_name
                                            ||
                                            'Admin' }}</span>
                                        pada {{ selectedTransaction?.deleted_at }}.
                                    </p>
                                    <p class="text-sm text-red-800 dark:text-red-300">
                                        <span class="font-bold">Alasan:</span> {{ selectedTransaction?.delete_reason ||
                                        '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Warning Alert (Show only if edited and NOT deleted) -->
                        <div v-if="selectedTransaction?.edited_at && !selectedTransaction?.deleted_at"
                            class="mb-6 bg-yellow-50 dark:bg-yellow-500/10 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 rounded-r-lg print:hidden">
                            <div class="flex items-start gap-3">
                                <AlertCircle
                                    class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <h4 class="font-bold text-yellow-900 dark:text-yellow-200 mb-1">⚠️ Transaksi Telah
                                        Diedit
                                    </h4>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-1">
                                        Diedit oleh <span class="font-semibold">{{ selectedTransaction?.editor_name ||
                                            'Admin'
                                        }}</span>
                                        pada {{ selectedTransaction?.edited_at }}.
                                    </p>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                        <span class="font-bold">Alasan:</span> {{ selectedTransaction?.edit_reason ||
                                            '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Store Header -->
                        <div class="text-center mb-6 pb-4 border-b border-dashed border-zinc-300 dark:border-zinc-700">
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">{{
                                selectedTransaction?.branch_name ||
                                'Cabang' }}</h2>
                            <p v-if="selectedTransaction?.branch_address" class="text-xs text-zinc-500 mt-1">{{
                                selectedTransaction.branch_address }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5">Struk Pembayaran</p>
                        </div>

                        <!-- Order Info -->
                        <div class="mb-4 space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-500">No. Order:</span>
                                <span class="font-medium text-zinc-900 dark:text-white">{{
                                    selectedTransaction?.order_number
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Tanggal:</span>
                                <span class="text-zinc-900 dark:text-white">{{ selectedTransaction?.date }} {{
                                    selectedTransaction?.time }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Pembayaran:</span>
                                <span class="text-zinc-900 dark:text-white">{{
                                    getPaymentMethodLabel(selectedTransaction?.payment_method || '') }}</span>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="border-t border-b border-dashed border-zinc-300 dark:border-zinc-700 py-4 mb-4">
                            <div v-for="(item, index) in selectedTransaction?.items" :key="index"
                                class="flex justify-between py-1.5 text-sm">
                                <div class="flex-1">
                                    <p class="text-zinc-900 dark:text-white">
                                        {{ item.name }}
                                        <span v-if="item.is_custom" class="text-xs text-orange-500">(Custom)</span>
                                    </p>
                                    <p class="text-xs text-zinc-500">{{ item.quantity }} x {{ formatRupiah(item.price)
                                    }}</p>
                                </div>
                                <span class="text-zinc-900 dark:text-white font-medium">{{ formatRupiah(item.subtotal)
                                }}</span>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="flex justify-between items-center text-lg font-bold">
                            <span class="text-zinc-900 dark:text-white">TOTAL</span>
                            <span class="text-orange-600 dark:text-orange-400">{{
                                formatRupiah(selectedTransaction?.total || 0)
                            }}</span>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-6 pt-4 border-t border-dashed border-zinc-300 dark:border-zinc-700">
                            <p class="text-xs text-zinc-500">Terima kasih atas kunjungan Anda!</p>
                            <p class="text-xs text-zinc-400 mt-1">Simpan struk ini sebagai bukti pembayaran</p>
                        </div>
                    </div>

                    <!-- Modal Actions (hidden on print) -->
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 print:hidden">
                        <Button class="w-full gap-2" @click="printReceipt">
                            <Printer class="h-4 w-4" />
                            Cetak Struk
                        </Button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Edit Items Modal (Admin Only) -->
        <transition name="fade">
            <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeEditModal"></div>
                <div
                    class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Edit Item Order</h3>
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
                        <!-- Warning Notice -->
                        <div
                            class="bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/20 rounded-lg p-4 mb-6">
                            <div class="flex gap-3">
                                <AlertCircle
                                    class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="font-medium text-yellow-900 dark:text-yellow-200 text-sm mb-1">Peringatan
                                    </h4>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300">Mengubah kuantitas item akan
                                        mengubah total transaksi. Pastikan perubahan ini benar dan berikan alasan yang
                                        jelas.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-zinc-900 dark:text-white">Daftar Item</h4>
                                <Button @click="addNewItem" variant="outline" size="sm"
                                    class="gap-2 text-orange-600 dark:text-orange-400 hover:text-orange-700 border-orange-300 hover:bg-orange-50 dark:hover:bg-orange-500/10">
                                    <Plus class="h-4 w-4" />
                                    Tambah Item
                                </Button>
                            </div>
                            <div v-for="(editItem, index) in editItems" :key="index"
                                class="bg-zinc-50 dark:bg-zinc-900/60 rounded-lg p-4 border border-zinc-200 dark:border-zinc-800">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <p class="text-xs text-zinc-500 mb-1">Produk</p>
                                        <select v-model="editItem.menu_id"
                                            @change="handleMenuChange(index, editItem.menu_id!)"
                                            class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                            <option v-for="menu in menus" :key="menu.id" :value="menu.id">
                                                {{ menu.nama }} - {{ formatRupiah(menu.harga) }}
                                            </option>
                                        </select>
                                    </div>
                                    <button v-if="editItems.length > 1" @click="deleteItem(index)"
                                        class="ml-3 mt-5 h-9 w-9 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors"
                                        title="Hapus Item">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs text-zinc-600 dark:text-zinc-400 block mb-1">Harga
                                            Satuan</label>
                                        <div
                                            class="px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ formatRupiah(editItem.price) }}
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs text-zinc-600 dark:text-zinc-400 block mb-1">Kuantitas</label>
                                        <Input v-model.number="editItem.quantity" type="number" min="1"
                                            class="w-full" />
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs text-zinc-600 dark:text-zinc-400 block mb-1">Subtotal</label>
                                        <div
                                            class="px-3 py-2 bg-orange-50 dark:bg-orange-500/10 rounded-lg text-sm font-semibold text-orange-600 dark:text-orange-400">
                                            {{ formatRupiah(editItem.price * editItem.quantity) }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="editItem.id" class="mt-2 text-xs text-zinc-500">
                                    Item asli: {{selectedTransaction?.items.find(i => i.id === editItem.id)?.name}} ×
                                    {{
                                        selectedTransaction?.items.find(i => i.id === editItem.id)?.quantity}}
                                </div>
                                <div v-else class="mt-2 text-xs text-green-600 dark:text-green-400 font-medium">
                                    ✨ Item Baru
                                </div>
                            </div>
                        </div>

                        <!-- Total Preview -->
                        <div
                            class="bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-lg p-4 mb-6">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Sebelumnya:</span>
                                    <span class="font-medium text-zinc-900 dark:text-white">{{
                                        formatRupiah(selectedTransaction?.total || 0) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Baru:</span>
                                    <span class="font-bold text-orange-600 dark:text-orange-400">{{
                                        formatRupiah(calculateNewTotal) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Reason -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Alasan Perubahan <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="editReason" rows="3"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                placeholder="Contoh: Koreksi pesanan - pelanggan salah memesan jumlah"
                                required></textarea>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3">
                        <Button @click="closeEditModal" variant="outline">
                            Batal
                        </Button>
                        <Button @click="saveEditedItems" class="bg-orange-600 hover:bg-orange-700 text-white">
                            Simpan Perubahan
                        </Button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Void Transaction Modal -->
        <transition name="fade">
            <div v-if="isVoidModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeVoidModal"></div>
                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full mx-4">
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Batalkan Transaksi</h3>
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
                        <!-- Warning Notice -->
                        <div
                            class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg p-4 mb-6">
                            <div class="flex gap-3">
                                <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="font-medium text-red-900 dark:text-red-200 text-sm mb-1">Peringatan</h4>
                                    <p class="text-xs text-red-700 dark:text-red-300">Tindakan ini akan membatalkan
                                        transaksi
                                        dan mengembalikan stok barang. Pastikan alasan pembatalan valid dan jelas.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Details -->
                        <div class="bg-zinc-50 dark:bg-zinc-900/60 rounded-lg p-4 mb-6 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Total Transaksi:</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">{{
                                    formatRupiah(selectedTransaction?.total || 0) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Tanggal:</span>
                                <span class="text-zinc-900 dark:text-white">{{ selectedTransaction?.date }} {{
                                    selectedTransaction?.time }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Jumlah Item:</span>
                                <span class="text-zinc-900 dark:text-white">{{ selectedTransaction?.items.length }}
                                    item</span>
                            </div>
                        </div>

                        <!-- Void Reason -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Alasan Pembatalan <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="voidReason" rows="4"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Contoh: Pelanggan membatalkan pesanan / Kesalahan input / dll. (minimal 10 karakter)"
                                required></textarea>
                            <p class="text-xs text-zinc-500 mt-1">Minimal 10 karakter</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3">
                        <Button @click="closeVoidModal" variant="outline">
                            Batal
                        </Button>
                        <Button @click="confirmVoidTransaction" class="bg-red-600 hover:bg-red-700 text-white">
                            <Ban class="h-4 w-4 mr-2" />
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
