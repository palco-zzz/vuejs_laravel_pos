<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    totalIncome: number;
    totalTransactions: number;
    activeBranches: number;
    topSellingMenus: {
        name: string;
        total_sold: number;
        icon: string | null;
    }[];
    branchPerformance: {
        id: number;
        name: string;
        address: string;
        transaction_count: number;
        total_income: number;
        latest_activity: string;
        status: string;
    }[];
    latestTransactions: {
        id: number;
        order_number: string;
        items: string;
        branch_name: string;
        total: number;
        time_ago: string;
        is_new: boolean;
    }[];
    chartLabels: string[];
    chartData: number[];
    isCashier: boolean;
}>();

const performanceTab = ref<'omset' | 'transaksi'>('omset');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatRupiahShort = (value: number) => {
    if (value >= 1000000) {
        return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
    } else if (value >= 1000) {
        return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
    }
    return formatRupiah(value);
};

// Calculate max value for chart height percentages
const maxChartValue = Math.max(...props.chartData, 1);
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Dashboard Content -->
        <div class="p-6 space-y-6">

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Card 1 (Total Income) - Clickable -> Report -->
                <Link href="/dashboard"
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-900/60 transition-colors group shadow-sm dark:shadow-none cursor-pointer block">
                <div class="flex justify-between items-start mb-4">
                    <div
                        class="p-2 bg-zinc-100 dark:bg-zinc-800/50 rounded-lg text-zinc-500 dark:text-zinc-400 group-hover:text-orange-500 dark:group-hover:text-orange-400 transition-colors">
                        <i data-lucide="banknote" class="h-5 w-5"></i>
                    </div>
                    <span
                        class="text-xs font-medium text-green-600 dark:text-green-500 bg-green-100 dark:bg-green-500/10 px-2 py-0.5 rounded-full flex items-center gap-1">
                        Hari Ini
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-xs font-medium mb-1">Total Pendapatan (Hari Ini)</span>
                    <h3 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{
                        formatRupiah(totalIncome) }}
                    </h3>
                </div>
                </Link>

                <!-- Card 2 (Transactions) - Clickable -> Report -->
                <Link href="/reports/transactions"
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-900/60 transition-colors group shadow-sm dark:shadow-none cursor-pointer block">
                <div class="flex justify-between items-start mb-4">
                    <div
                        class="p-2 bg-zinc-100 dark:bg-zinc-800/50 rounded-lg text-zinc-500 dark:text-zinc-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">
                        <i data-lucide="receipt" class="h-5 w-5"></i>
                    </div>
                    <span
                        class="text-xs font-medium text-green-600 dark:text-green-500 bg-green-100 dark:bg-green-500/10 px-2 py-0.5 rounded-full flex items-center gap-1">
                        Hari Ini
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-xs font-medium mb-1">Total Transaksi</span>
                    <h3 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ totalTransactions
                    }} <span class="text-sm font-normal text-zinc-500">Nota</span></h3>
                </div>
                </Link>

                <!-- Card 3 (Active Branches) - Clickable -> Branch Management -->
                <Link href="/branch"
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-900/60 transition-colors group shadow-sm dark:shadow-none cursor-pointer block">
                <div class="flex justify-between items-start mb-4">
                    <div
                        class="p-2 bg-zinc-100 dark:bg-zinc-800/50 rounded-lg text-zinc-500 dark:text-zinc-400 group-hover:text-purple-500 dark:group-hover:text-purple-400 transition-colors">
                        <i data-lucide="store" class="h-5 w-5"></i>
                    </div>
                    <span class="text-xs font-medium text-zinc-500">Live Status</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-xs font-medium mb-1">Cabang Aktif</span>
                    <h3 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ activeBranches }}
                        <span class="text-sm font-normal text-zinc-500">/ {{ activeBranches }} Buka</span>
                    </h3>
                </div>
                </Link>

                <!-- Card 4 (Top Selling) - Clickable -> Menu Management -->
                <Link href="/menu"
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-900/60 transition-colors group cursor-pointer relative overflow-hidden shadow-sm dark:shadow-none block">
                <div
                    class="absolute inset-0 bg-gradient-to-tr from-orange-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div
                        class="p-2 bg-zinc-100 dark:bg-zinc-800/50 rounded-lg text-zinc-500 dark:text-zinc-400 group-hover:text-yellow-500 dark:group-hover:text-yellow-400 transition-colors">
                        <i data-lucide="star" class="h-5 w-5"></i>
                    </div>
                    <span
                        class="text-xs font-medium text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-400/10 px-2 py-0.5 rounded-full">Favorit</span>
                </div>
                <div class="flex flex-col relative z-10">
                    <span class="text-zinc-500 text-xs font-medium mb-1">Menu Terlaris</span>
                    <h3 class="text-lg font-medium tracking-tight text-zinc-900 dark:text-white truncate">
                        {{ topSellingMenus[0]?.name || 'Belum ada data' }}
                    </h3>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Terjual {{ topSellingMenus[0]?.total_sold || 0 }} Porsi
                    </span>
                </div>
                </Link>
            </div>

            <!-- Main Split Layout -->
            <!-- ADMIN VIEW -->
            <div v-if="!isCashier" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column: Branch Performance (2 cols wide) -->
                <div class="lg:col-span-2 flex flex-col gap-6">

                    <!-- Live Sales Chart Section -->
                    <div
                        class="bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm dark:shadow-none">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Performa Cabang
                                (Real-time)</h3>
                            <div
                                class="flex bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-md p-0.5">
                                <button @click="performanceTab = 'omset'"
                                    :class="performanceTab === 'omset' ? 'bg-white border text-zinc-900 border-zinc-200 dark:border-transparent dark:bg-white ' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-300'"
                                    class="px-3 py-1 text-xs font-medium rounded shadow-sm transition-all">
                                    Omzet
                                </button>
                                <button @click="performanceTab = 'transaksi'"
                                    :class="performanceTab === 'transaksi' ? 'bg-white border text-zinc-900 border-zinc-200 dark:border-transparent dark:bg-white ' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-300'"
                                    class="px-3 py-1 text-xs font-medium rounded shadow-sm transition-all">
                                    Transaksi
                                </button>
                            </div>
                        </div>

                        <!-- Custom Table for Branches -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500 uppercase tracking-wider">
                                        <th class="pb-3 font-medium pl-2">Cabang</th>
                                        <th class="pb-3 font-medium text-center">Status</th>
                                        <th class="pb-3 font-medium text-right">
                                            {{ performanceTab === 'omset' ? 'Omzet Hari Ini' : 'Jumlah Transaksi' }}
                                        </th>
                                        <th class="pb-3 font-medium text-center">Total Transaksi</th>
                                        <th class="pb-3 font-medium text-right pr-2">Aktivitas Terakhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
                                    <tr v-for="branch in branchPerformance" :key="branch.id"
                                        class="group hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                        <td class="py-4 pl-2">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="h-8 w-8 rounded bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-400 font-medium text-xs">
                                                    {{ String(branch.id).padStart(2, '0') }}</div>
                                                <div>
                                                    <p class="text-zinc-900 dark:text-zinc-200 font-medium">{{
                                                        branch.name }}</p>
                                                    <p class="text-zinc-500 text-xs">{{ branch.address }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 text-center">
                                            <span
                                                :class="branch.status === 'active' ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' : 'bg-gray-400'"
                                                class="inline-flex h-2 w-2 rounded-full"></span>
                                        </td>
                                        <td class="py-4 text-right text-zinc-700 dark:text-zinc-300 font-medium">
                                            {{ performanceTab === 'omset' ? formatRupiah(branch.total_income) :
                                                branch.transaction_count + ' Transaksi' }}
                                        </td>
                                        <td class="py-4 text-center">
                                            <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{
                                                branch.transaction_count }}</span>
                                        </td>
                                        <td class="py-4 text-right pr-2">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{
                                                branch.latest_activity }}</span>
                                        </td>
                                    </tr>

                                    <!-- Add Branch Button -->
                                    <tr v-if="branchPerformance.length === 0">
                                        <td colspan="5" class="py-8 text-center text-zinc-500">
                                            Belum ada cabang terdaftar
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="pt-4 text-center">
                                            <Link href="/branch"
                                                class="w-full border border-dashed border-zinc-300 dark:border-zinc-800 rounded-lg py-3 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-300 hover:border-zinc-400 dark:hover:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900/30 transition-all flex items-center justify-center gap-2">
                                            <i data-lucide="plus" class="h-4 w-4"></i> Tambah Cabang Baru
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Visual Chart (Real Data) -->
                    <div
                        class="bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm dark:shadow-none">
                        <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-200 mb-6">Tren Penjualan
                            Mingguan</h3>
                        <div class="h-48 flex items-end justify-between gap-2">
                            <div v-for="(value, index) in chartData" :key="index"
                                :style="{ height: (value / maxChartValue * 100) + '%' }" :class="[
                                    'w-full rounded-sm relative group transition-colors min-h-[10%]',
                                    index === 6 ? 'bg-zinc-200 dark:bg-zinc-700/50 border-t-2 border-dashed border-zinc-400 dark:border-zinc-500' :
                                        index === 5 && value === Math.max(...chartData) ? 'bg-orange-500 dark:bg-orange-600 hover:bg-orange-600 dark:hover:bg-orange-500 shadow-[0_0_15px_rgba(234,88,12,0.3)]' :
                                            'bg-zinc-100 dark:bg-zinc-800/50 hover:bg-zinc-200 dark:hover:bg-zinc-800'
                                ]">
                                <div
                                    class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-zinc-900 dark:bg-zinc-800 text-white text-[10px] py-1 px-2 rounded border border-zinc-700 whitespace-nowrap z-10">
                                    {{ formatRupiahShort(value) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-zinc-500 font-mono">
                            <span v-for="(label, index) in chartLabels" :key="index">{{ label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Operational Details -->
                <div class="flex flex-col gap-6">

                    <!-- Top Selling Menus Widget -->
                    <div
                        class="bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm dark:shadow-none">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Menu Terlaris</h3>
                        </div>

                        <div class="space-y-4">
                            <div v-if="topSellingMenus.length === 0" class="text-center text-zinc-500 text-xs py-4">
                                Belum ada data penjualan
                            </div>

                            <div v-for="(item, index) in topSellingMenus" :key="index" class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 rounded-lg text-lg">
                                    {{ item.icon || '🍽️' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200 truncate pr-2">
                                            {{ item.name }}
                                        </h4>
                                        <span class="text-xs font-bold text-orange-600 dark:text-orange-400 shrink-0">
                                            {{ item.total_sold }} Sold
                                        </span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-orange-500 h-full rounded-full"
                                            :style="{ width: `${(item.total_sold / (topSellingMenus[0]?.total_sold || 1)) * 100}%` }">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-center">
                            <Link href="/admin/menu"
                                class="text-xs text-orange-500 hover:text-orange-600 font-medium cursor-pointer">Lihat
                            Analisa Menu</Link>
                        </div>
                    </div>

                    <!-- Recent Orders Stream -->
                    <div
                        :class="isCashier ? 'bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 flex-1 shadow-sm dark:shadow-none min-h-[400px]' : 'bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 flex-1 shadow-sm dark:shadow-none'">
                        <div class="flex items-center justify-between mb-4">
                            <h3
                                :class="isCashier ? 'text-lg font-semibold text-zinc-900 dark:text-zinc-200' : 'text-sm font-medium text-zinc-900 dark:text-zinc-200'">
                                {{ isCashier ? 'Transaksi Hari Ini' : 'Pesanan Masuk' }}
                            </h3>
                            <Link v-if="isCashier" href="/pos/history"
                                class="text-xs text-orange-500 hover:text-orange-600 font-medium">
                            Lihat Semua →
                            </Link>
                        </div>
                        <div class="space-y-0 relative">
                            <!-- Timeline line -->
                            <div v-if="latestTransactions.length > 0"
                                class="absolute left-2 top-2 bottom-2 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                            <!-- Empty state -->
                            <div v-if="latestTransactions.length === 0" class="py-8 text-center text-zinc-500 text-sm">
                                Belum ada pesanan masuk
                            </div>

                            <!-- Dynamic Orders -->
                            <div v-for="(order, index) in latestTransactions" :key="order.id" class="relative pl-6"
                                :class="index < latestTransactions.length - 1 ? 'pb-6' : 'pb-0'">
                                <div
                                    class="absolute left-0 top-1.5 h-4 w-4 rounded-full bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 flex items-center justify-center z-10">
                                    <div v-if="order.is_new" class="h-1.5 w-1.5 rounded-full bg-orange-500"></div>
                                </div>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium"
                                            :class="order.is_new ? 'text-zinc-900 dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-300'">
                                            {{ order.items }}</p>
                                        <p class="text-xs text-zinc-500">Cabang: {{ order.branch_name }} • {{
                                            order.order_number }}</p>
                                    </div>
                                    <span class="text-xs font-medium"
                                        :class="order.is_new ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-500 dark:text-zinc-400'">{{
                                            formatRupiah(order.total) }}</span>
                                </div>
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-600 mt-1 block">
                                    {{ order.is_new ? 'Baru saja' : order.time_ago }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CASHIER VIEW: Clean, Simple Layout -->
            <div v-else class="space-y-6">
                <!-- Cashier: Full-width Transaction List -->
                <div
                    class="bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm dark:shadow-none">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-200">Transaksi Hari Ini</h3>
                            <p class="text-sm text-zinc-500 mt-1">Pesanan terbaru dari cabang Anda</p>
                        </div>
                        <Link href="/pos/history"
                            class="text-sm text-orange-500 hover:text-orange-600 font-medium flex items-center gap-1">
                        Lihat Semua →
                        </Link>
                    </div>

                    <div class="space-y-0 relative">
                        <!-- Timeline line -->
                        <div v-if="latestTransactions.length > 0"
                            class="absolute left-2 top-2 bottom-2 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                        <!-- Empty state -->
                        <div v-if="latestTransactions.length === 0" class="py-12 text-center">
                            <div
                                class="h-16 w-16 mx-auto mb-4 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center">
                                <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                            </div>
                            <p class="text-zinc-500 dark:text-zinc-400 mb-4">Belum ada pesanan hari ini</p>
                            <Link href="/pos"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium transition-colors">
                            Buat Transaksi Baru
                            </Link>
                        </div>

                        <!-- Dynamic Orders -->
                        <div v-for="(order, index) in latestTransactions" :key="order.id" class="relative pl-6"
                            :class="index < latestTransactions.length - 1 ? 'pb-6' : 'pb-0'">
                            <div
                                class="absolute left-0 top-1.5 h-4 w-4 rounded-full bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 flex items-center justify-center z-10">
                                <div v-if="order.is_new" class="h-1.5 w-1.5 rounded-full bg-orange-500 animate-pulse">
                                </div>
                            </div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium"
                                        :class="order.is_new ? 'text-zinc-900 dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-300'">
                                        {{ order.items }}</p>
                                    <p class="text-xs text-zinc-500">{{ order.order_number }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold"
                                        :class="order.is_new ? 'text-orange-600 dark:text-orange-400' : 'text-zinc-700 dark:text-zinc-300'">{{
                                        formatRupiah(order.total) }}</span>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-600 block mt-0.5">{{
                                        order.is_new ? 'Baru saja' : order.time_ago }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>