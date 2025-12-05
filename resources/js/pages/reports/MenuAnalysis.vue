<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { TrendingUp, TrendingDown, Package, DollarSign, BarChart3, PieChart, AlertTriangle } from 'lucide-vue-next';

interface MenuPerformer {
    menu_id: number;
    menu_name: string;
    category_name: string;
    price: number;
    icon: string;
    total_sold: number;
    total_revenue: number;
}

interface CategoryData {
    category_id: number;
    category_name: string;
    total_sold: number;
    total_revenue: number;
    percentage: number;
}

interface ZeroSalesItem {
    menu_id: number;
    menu_name: string;
    category_name: string;
    price: number;
    stock: number;
}

interface Summary {
    total_items_sold: number;
    total_revenue: number;
    best_selling_item: {
        name: string;
        sold: number;
    } | null;
    highest_revenue_item: {
        name: string;
        revenue: number;
    } | null;
    zero_sales_count: number;
}

interface Branch {
    id: number;
    nama: string;
}

const props = defineProps<{
    topPerformers: MenuPerformer[];
    categoryBreakdown: CategoryData[];
    zeroSales: ZeroSalesItem[];
    summary: Summary;
    branches: Branch[];
    filters: {
        date_range: string;
        branch_id?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analisa Menu', href: '/reports/menu-analysis' },
];

// Filters
const selectedDateRange = ref(props.filters.date_range);
const selectedBranch = ref(props.filters.branch_id || '');
const sortBy = ref<'quantity' | 'revenue'>('quantity');
const sortDirection = ref<'asc' | 'desc'>('desc');

const dateRangeOptions = [
    { value: 'today', label: 'Hari Ini' },
    { value: 'yesterday', label: 'Kemarin' },
    { value: 'this_week', label: 'Minggu Ini' },
    { value: 'last_week', label: 'Minggu Lalu' },
    { value: 'this_month', label: 'Bulan Ini' },
    { value: 'last_month', label: 'Bulan Lalu' },
    { value: 'this_year', label: 'Tahun Ini' },
];

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const applyFilters = () => {
    router.get('/reports/menu-analysis', {
        date_range: selectedDateRange.value,
        branch_id: selectedBranch.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Sorted table data
const sortedMenuData = computed(() => {
    const data = [...props.topPerformers];
    const field = sortBy.value === 'quantity' ? 'total_sold' : 'total_revenue';

    data.sort((a, b) => {
        if (sortDirection.value === 'asc') {
            return a[field] - b[field];
        }
        return b[field] - a[field];
    });

    return data;
});

const toggleSort = (field: 'quantity' | 'revenue') => {
    if (sortBy.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDirection.value = 'desc';
    }
};

// Chart colors
const chartColors = ['#f97316', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];
</script>

<template>

    <Head title="Analisa Menu" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header with Filters -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="h-12 w-12 bg-orange-100 dark:bg-orange-500/10 rounded-xl flex items-center justify-center">
                        <BarChart3 class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Analisa Menu</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Pantau performa penjualan setiap menu</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <select v-model="selectedDateRange" @change="applyFilters"
                        class="px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm focus:ring-2 focus:ring-orange-500">
                        <option v-for="option in dateRangeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <select v-model="selectedBranch" @change="applyFilters"
                        class="px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm focus:ring-2 focus:ring-orange-500">
                        <option value="">Semua Cabang</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                            {{ branch.nama }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Items Sold -->
                <div
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div
                            class="h-10 w-10 bg-blue-100 dark:bg-blue-500/10 rounded-lg flex items-center justify-center">
                            <Package class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <TrendingUp class="h-5 w-5 text-green-500" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ summary.total_items_sold }}</h3>
                    <p class="text-sm text-zinc-500 mt-1">Total Item Terjual</p>
                </div>

                <!-- Total Revenue -->
                <div
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div
                            class="h-10 w-10 bg-green-100 dark:bg-green-500/10 rounded-lg flex items-center justify-center">
                            <DollarSign class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <TrendingUp class="h-5 w-5 text-green-500" />
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ formatRupiah(summary.total_revenue)
                        }}</h3>
                    <p class="text-sm text-zinc-500 mt-1">Total Pendapatan</p>
                </div>

                <!-- Best Selling Item -->
                <div
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div
                            class="h-10 w-10 bg-orange-100 dark:bg-orange-500/10 rounded-lg flex items-center justify-center">
                            <TrendingUp class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </div>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                        {{ summary.best_selling_item?.name || '-' }}
                    </h3>
                    <p class="text-xs text-zinc-500 mt-1">
                        Terlaris: {{ summary.best_selling_item?.sold || 0 }} Porsi
                    </p>
                </div>

                <!-- Zero Sales Count -->
                <div
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div
                            class="h-10 w-10 bg-red-100 dark:bg-red-500/10 rounded-lg flex items-center justify-center">
                            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <TrendingDown class="h-5 w-5 text-red-500" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ summary.zero_sales_count }}</h3>
                    <p class="text-sm text-zinc-500 mt-1">Menu Tidak Terjual</p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top 5 Bar Chart -->
                <div
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Top 5 Menu Terlaris</h3>
                    <div class="space-y-4">
                        <div v-for="(item, index) in topPerformers.slice(0, 5)" :key="item.menu_id"
                            class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-8 text-center">
                                <span class="text-2xl">{{ item.icon }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-baseline mb-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{
                                        item.menu_name }}</p>
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400 ml-2">{{
                                        item.total_sold }}</span>
                                </div>
                                <div class="h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all" :style="{
                                        width: ((item.total_sold / topPerformers[0].total_sold) * 100) + '%',
                                        backgroundColor: chartColors[index % chartColors.length]
                                    }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Pie Chart -->
                <div
                    class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Penjualan Per Kategori</h3>
                    <div class="space-y-3">
                        <div v-for="(cat, index) in categoryBreakdown" :key="cat.category_id"
                            class="flex items-center justify-between">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="h-4 w-4 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: chartColors[index % chartColors.length] }"></div>
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ cat.category_name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ cat.total_sold
                                    }}</span>
                                <span class="text-xs text-zinc-500 w-12 text-right">{{ cat.percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Table -->
            <div
                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Detail Performa Menu</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Rank</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Menu</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Kategori</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Harga</th>
                                <th @click="toggleSort('quantity')"
                                    class="px-6 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider cursor-pointer hover:text-orange-500">
                                    Qty Terjual
                                    <span v-if="sortBy === 'quantity'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                                <th @click="toggleSort('revenue')"
                                    class="px-6 py-3 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider cursor-pointer hover:text-orange-500">
                                    Total Revenue
                                    <span v-if="sortBy === 'revenue'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="(menu, index) in sortedMenuData" :key="menu.menu_id"
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-bold"
                                        :class="index === 0 ? 'bg-orange-100 dark:bg-orange-500/20 text-orange-600' :
                                            index === 1 ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-600' :
                                                index === 2 ? 'bg-green-100 dark:bg-green-500/20 text-green-600' :
                                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-600'">
                                        #{{ index + 1 }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">{{ menu.icon }}</span>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{
                                            menu.menu_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="text-xs px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        {{ menu.category_name }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ formatRupiah(menu.price) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ menu.total_sold
                                        }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                        {{ formatRupiah(menu.total_revenue) }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="sortedMenuData.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                    Belum ada data penjualan
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zero Sales Warning -->
            <div v-if="zeroSales.length > 0"
                class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-800 rounded-xl p-6">
                <div class="flex items-start gap-3 mb-4">
                    <AlertTriangle class="h-6 w-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="text-lg font-semibold text-red-900 dark:text-red-300">Menu Tidak Terjual (Dead Stock)
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-1">{{ zeroSales.length }} menu belum terjual
                            dalam periode ini</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-4">
                    <div v-for="item in zeroSales.slice(0, 6)" :key="item.menu_id"
                        class="bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-800 rounded-lg p-3">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ item.menu_name }}</p>
                        <p class="text-xs text-zinc-500 mt-1">{{ item.category_name }} • Stok: {{ item.stock }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
