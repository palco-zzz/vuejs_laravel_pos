<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import GlassCard from '@/components/ui/GlassCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { DollarSign, Receipt, Store, Package } from 'lucide-vue-next';

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

// Debug: Log chart data on mount
onMounted(() => {
    console.log('Chart Labels:', props.chartLabels);
    console.log('Chart Data:', props.chartData);
    console.log('Chart Data Length:', props.chartData?.length);
    console.log('Max Chart Value:', Math.max(...(props.chartData || [0]), 1));
});

const performanceTab = ref<'omset' | 'transaksi'>('omset');
const hoveredIndex = ref<number | null>(null);

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
const maxChartValue = Math.max(...(props.chartData || [0]), 1);

// Stat card configurations with gradient colors
const statCards = [
    {
        title: 'Total Pendapatan',
        subtitle: 'Hari Ini',
        value: formatRupiah(props.totalIncome),
        icon: DollarSign,
        gradient: 'from-orange-500 to-amber-500',
        shadowColor: 'shadow-orange-200 dark:shadow-orange-500/20',
        href: '/dashboard',
        show: true,
    },
    {
        title: 'Total Transaksi',
        subtitle: 'Hari Ini',
        value: `${props.totalTransactions} Nota`,
        icon: Receipt,
        gradient: 'from-blue-500 to-cyan-500',
        shadowColor: 'shadow-blue-200 dark:shadow-blue-500/20',
        href: props.isCashier ? '/pos/history' : '/reports/transactions',
        show: true,
    },
    {
        title: 'Cabang Aktif',
        subtitle: 'Live Status',
        value: `${props.activeBranches} / ${props.activeBranches} Buka`,
        icon: Store,
        gradient: 'from-emerald-500 to-teal-500',
        shadowColor: 'shadow-emerald-200 dark:shadow-emerald-500/20',
        href: '/branch',
        show: !props.isCashier,
    },
    {
        title: 'Menu Terlaris',
        subtitle: `Terjual ${props.topSellingMenus[0]?.total_sold || 0} Porsi`,
        value: props.topSellingMenus[0]?.name || 'Belum ada data',
        icon: Package,
        gradient: 'from-purple-500 to-pink-500',
        shadowColor: 'shadow-purple-200 dark:shadow-purple-500/20',
        href: props.isCashier ? '/pos/top-menus' : '/reports/menu-analysis',
        show: true,
    },
];
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Dashboard Content -->
        <div class="p-6 space-y-6 animate-[fadeInUp_0.6s_ease-out]">

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between md:items-end gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-slate-900 dark:text-white tracking-tight">Dashboard</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Ringkasan performa bisnis hari ini.</p>
                </div>
                <div
                    class="px-4 py-2 bg-white dark:bg-slate-800 rounded-full text-sm font-medium shadow-sm border border-slate-100 dark:border-slate-700 text-slate-600 dark:text-slate-300 flex items-center gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Hari Ini
                </div>
            </div>

            <!-- Gradient Stat Cards Grid -->
            <div
                :class="isCashier ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' : 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6'">
                <template v-for="card in statCards" :key="card.title">
                    <Link v-if="card.show" :href="card.href"
                        class="group relative overflow-hidden rounded-[2rem] p-6 cursor-pointer hover:-translate-y-1 transition-all duration-200 ease-out
                               transform-gpu will-change-transform
                               bg-white/95 md:bg-white/60 dark:bg-slate-900/95 md:dark:bg-slate-900/70 md:backdrop-blur-xl
                               border border-white/40 dark:border-slate-700/50
                               shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]
                               hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgba(0,0,0,0.3)]">
                        <!-- Inner Gradient Overlay -->
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-white/30 to-transparent dark:from-slate-800/20 dark:to-slate-900/10 pointer-events-none" />

                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div
                                :class="['p-3.5 rounded-2xl bg-gradient-to-br text-white shadow-lg', card.gradient, card.shadowColor]">
                                <component :is="card.icon" class="w-[22px] h-[22px]" />
                            </div>
                            <div
                                class="flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                Info
                            </div>
                        </div>
                        <div class="relative z-10">
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-1">{{ card.title }}</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">{{ card.value
                            }}</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ card.subtitle }}</p>
                        </div>
                        <!-- Background Blur Effect -->
                        <div
                            class="absolute -bottom-8 -right-8 w-32 h-32 bg-gradient-to-br from-slate-100/50 dark:from-slate-700/30 to-transparent rounded-full blur-2xl group-hover:scale-150 transition-transform duration-300 ease-out transform-gpu" />
                    </Link>
                </template>
            </div>

            <!-- Main Split Layout -->
            <!-- ADMIN VIEW -->
            <div v-if="!isCashier" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column: Branch Performance (2 cols wide) -->
                <div class="lg:col-span-2 flex flex-col gap-6">

                    <!-- Branch Performance Table -->
                    <GlassCard>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-lg text-slate-800 dark:text-white">Performa Cabang (Real-time)
                            </h3>
                            <div class="flex gap-1">
                                <button @click="performanceTab = 'omset'"
                                    :class="performanceTab === 'omset' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'"
                                    class="px-3 py-1 text-xs font-bold rounded-lg shadow-sm transition-all">
                                    Omzet
                                </button>
                                <button @click="performanceTab = 'transaksi'"
                                    :class="performanceTab === 'transaksi' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'"
                                    class="px-3 py-1 text-xs font-bold rounded-lg shadow-sm transition-all">
                                    Transaksi
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="text-xs text-slate-400 dark:text-slate-500 uppercase font-bold border-b border-slate-100 dark:border-slate-700">
                                    <tr>
                                        <th class="pb-3 pl-2">Cabang</th>
                                        <th class="pb-3 text-center">Status</th>
                                        <th class="pb-3 text-right">
                                            {{ performanceTab === 'omset' ? 'Omzet Hari Ini' : 'Jumlah Transaksi' }}
                                        </th>
                                        <th class="pb-3 text-center">Total Transaksi</th>
                                        <th class="pb-3 text-right pr-2">Aktivitas Terakhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                    <tr v-for="branch in branchPerformance" :key="branch.id"
                                        class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="py-4 pl-2">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500 dark:text-slate-400">
                                                    {{ String(branch.id).padStart(2, '0') }}
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-800 dark:text-white text-sm">{{
                                                        branch.name }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-400 dark:text-slate-500">{{
                                                        branch.address }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 text-center">
                                            <div :class="branch.status === 'active' ? 'bg-emerald-500 shadow-sm shadow-emerald-200 dark:shadow-emerald-500/30' : 'bg-gray-400'"
                                                class="w-2.5 h-2.5 rounded-full mx-auto" />
                                        </td>
                                        <td class="py-4 text-right font-bold text-slate-700 dark:text-slate-300">
                                            {{ performanceTab === 'omset' ? formatRupiah(branch.total_income) :
                                                branch.transaction_count + ' Transaksi' }}
                                        </td>
                                        <td class="py-4 text-center font-medium text-slate-600 dark:text-slate-400">
                                            {{ branch.transaction_count }}
                                        </td>
                                        <td class="py-4 text-right text-xs text-slate-400 dark:text-slate-500 pr-2">
                                            {{ branch.latest_activity }}
                                        </td>
                                    </tr>

                                    <tr v-if="branchPerformance.length === 0">
                                        <td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                            Belum ada cabang terdaftar
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="pt-4">
                                            <Link href="/branch"
                                                class="w-full border border-dashed border-slate-300 dark:border-slate-700 rounded-lg py-3 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-400 dark:hover:border-slate-600 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all flex items-center justify-center gap-2">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Tambah Cabang Baru
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </GlassCard>

                    <!-- Weekly Sales Chart -->
                    <GlassCard>
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-12">Tren Penjualan Mingguan</h3>

                        <!-- Chart Container -->
                        <div v-if="chartData && chartData.length > 0"
                            class="h-48 flex items-end justify-between gap-4 px-2 pt-6 pb-2">
                            <div v-for="(value, index) in chartData" :key="index"
                                class="flex-1 flex flex-col items-center gap-2" @mouseenter="hoveredIndex = index"
                                @mouseleave="hoveredIndex = null">
                                <div
                                    class="relative w-full bg-slate-100 dark:bg-slate-700/30 rounded-t-xl overflow-visible h-48 flex items-end cursor-pointer">

                                    <!-- Interactive Tooltip -->
                                    <Transition name="fade">
                                        <div v-if="hoveredIndex === index"
                                            class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-20 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold py-1.5 px-3 rounded-lg shadow-xl whitespace-nowrap pointer-events-none transition-all duration-200">
                                            {{ value === 0 ? 'Rp 0' : formatRupiah(value) }}
                                            <!-- Tooltip Arrow -->
                                            <div
                                                class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-white">
                                            </div>
                                        </div>
                                    </Transition>

                                    <!-- Bar -->
                                    <div :style="{ height: Math.max((value / maxChartValue * 100), 2) + '%' }" :class="[
                                        'w-full transition-all duration-300 rounded-t-lg',
                                        value > 0 ? 'bg-orange-500' : 'bg-transparent',
                                        hoveredIndex === index ? 'opacity-100 scale-[1.02]' : 'opacity-80'
                                    ]">
                                    </div>
                                </div>
                                <span :class="[
                                    'text-xs font-bold uppercase transition-colors duration-200',
                                    hoveredIndex === index ? 'text-orange-600 dark:text-orange-400' : 'text-slate-500 dark:text-slate-400'
                                ]">
                                    {{ chartLabels[index] }}
                                </span>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="h-48 flex items-center justify-center">
                            <div class="text-center text-slate-500 dark:text-slate-400">
                                <p class="text-sm">Belum ada data grafik</p>
                                <p class="text-xs mt-1">Data akan muncul setelah ada transaksi</p>
                            </div>
                        </div>
                    </GlassCard>
                </div>

                <!-- Right Column: Operational Details -->
                <div class="flex flex-col gap-6">

                    <!-- Top Selling Menus Widget -->
                    <GlassCard>
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-6">Menu Terlaris</h3>

                        <div class="space-y-5">
                            <div v-if="topSellingMenus.length === 0"
                                class="text-center text-slate-500 dark:text-slate-400 text-xs py-4">
                                Belum ada data penjualan
                            </div>

                            <div v-for="(item, index) in topSellingMenus" :key="index">
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ item.name }}</span>
                                    <span class="font-bold text-orange-600 dark:text-orange-400 text-xs">{{
                                        item.total_sold }}
                                        Sold</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-orange-500 h-full rounded-full transition-all duration-500"
                                        :style="{ width: `${(item.total_sold / (topSellingMenus[0]?.total_sold || 1)) * 100}%` }" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <Link href="/reports/menu-analysis"
                                class="text-xs text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300 font-medium cursor-pointer transition-colors">
                                Lihat Analisa Menu →
                            </Link>
                        </div>
                    </GlassCard>

                    <!-- Recent Orders Stream -->
                    <GlassCard class="flex-1">
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-6">Pesanan Masuk</h3>

                        <div class="flex-1 overflow-y-auto space-y-6 pr-2 scrollbar-hide max-h-[500px]">
                            <!-- Empty state -->
                            <div v-if="latestTransactions.length === 0"
                                class="py-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                                Belum ada pesanan masuk
                            </div>

                            <!-- Timeline -->
                            <div v-for="(order, index) in latestTransactions" :key="order.id"
                                class="flex gap-3 relative pl-4"
                                :class="index < latestTransactions.length - 1 ? 'pb-6 border-l border-slate-100 dark:border-slate-800' : ''">
                                <div
                                    class="absolute -left-[5px] top-0 w-2.5 h-2.5 rounded-full bg-white dark:bg-slate-900 border-2 border-orange-500" />
                                <div class="flex-1 -mt-1">
                                    <p class="text-sm font-bold text-slate-800 dark:text-white leading-snug">{{
                                        order.items }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">{{ order.branch_name
                                    }} • {{
                                            order.time_ago }}</p>
                                </div>
                            </div>
                        </div>
                    </GlassCard>
                </div>

            </div>

            <!-- CASHIER VIEW: Clean, Simple Layout -->
            <div v-else class="space-y-6">
                <GlassCard>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Transaksi Hari Ini</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pesanan terbaru dari cabang Anda
                            </p>
                        </div>
                        <Link href="/pos/history"
                            class="text-sm text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300 font-medium flex items-center gap-1 transition-colors">
                            Lihat Semua →
                        </Link>
                    </div>

                    <div class="space-y-0 relative">
                        <!-- Timeline line -->
                        <div v-if="latestTransactions.length > 0"
                            class="absolute left-2 top-2 bottom-2 w-px bg-slate-200 dark:bg-slate-700" />

                        <!-- Empty state -->
                        <div v-if="latestTransactions.length === 0" class="py-12 text-center">
                            <div
                                class="h-16 w-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                                <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 mb-4">Belum ada pesanan hari ini</p>
                            <Link href="/pos"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium transition-colors">
                                Buat Transaksi Baru
                            </Link>
                        </div>

                        <!-- Dynamic Orders -->
                        <div v-for="(order, index) in latestTransactions" :key="order.id" class="relative pl-6"
                            :class="index < latestTransactions.length - 1 ? 'pb-6' : 'pb-0'">
                            <div
                                class="absolute left-0 top-1.5 h-4 w-4 rounded-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 flex items-center justify-center z-10">
                                <div v-if="order.is_new" class="h-1.5 w-1.5 rounded-full bg-orange-500 animate-pulse" />
                            </div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium"
                                        :class="order.is_new ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300'">
                                        {{ order.items }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ order.order_number }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold"
                                        :class="order.is_new ? 'text-orange-600 dark:text-orange-400' : 'text-slate-700 dark:text-slate-300'">
                                        {{ formatRupiah(order.total) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block mt-0.5">
                                        {{ order.is_new ? 'Baru saja' : order.time_ago }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </GlassCard>
            </div>
        </div>
    </AppLayout>

</template>

<style scoped>
/* Hide scrollbar but keep functionality */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Fade in up animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
        filter: blur(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
    }
}
</style>