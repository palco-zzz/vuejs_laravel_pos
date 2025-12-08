<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Trophy, TrendingUp, Calendar, Filter, X, Star, Package } from 'lucide-vue-next';

interface TopMenu {
    rank: number;
    menu_id: number;
    menu_name: string;
    category_name: string;
    icon: string;
    total_sold: number;
    total_revenue: number;
}

const props = defineProps<{
    topMenus: TopMenu[];
    summary: {
        total_items_sold: number;
        total_revenue: number;
        unique_menus: number;
    };
    branchName: string;
    filters: {
        start_date: string;
        end_date: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Menu Terlaris', href: '/pos/top-menus' },
];

// Filter state
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const applyFilters = () => {
    router.get('/pos/top-menus', {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDate.value = firstDayOfMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    applyFilters();
};

const setQuickFilter = (type: 'today' | 'week' | 'month') => {
    const today = new Date();

    if (type === 'today') {
        startDate.value = today.toISOString().split('T')[0];
        endDate.value = today.toISOString().split('T')[0];
    } else if (type === 'week') {
        const weekAgo = new Date(today);
        weekAgo.setDate(today.getDate() - 7);
        startDate.value = weekAgo.toISOString().split('T')[0];
        endDate.value = today.toISOString().split('T')[0];
    } else if (type === 'month') {
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        startDate.value = firstDayOfMonth.toISOString().split('T')[0];
        endDate.value = today.toISOString().split('T')[0];
    }

    applyFilters();
};

// Get medal/badge for top 3
const getRankBadge = (rank: number) => {
    switch (rank) {
        case 1:
            return { icon: '🥇', class: 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-700 dark:text-yellow-400' };
        case 2:
            return { icon: '🥈', class: 'bg-zinc-200 dark:bg-zinc-500/20 text-zinc-700 dark:text-zinc-300' };
        case 3:
            return { icon: '🥉', class: 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400' };
        default:
            return { icon: String(rank), class: 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' };
    }
};
</script>

<template>

    <Head title="Menu Terlaris" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="h-12 w-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/25">
                        <Trophy class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Menu Terlaris</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cabang {{ branchName }}</p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-500/10 rounded-lg">
                            <Package class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Item Terjual</p>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ summary.total_items_sold }}
                                <span class="text-sm font-normal text-zinc-500">Porsi</span>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-500/10 rounded-lg">
                            <TrendingUp class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Pendapatan</p>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{
                                formatRupiah(summary.total_revenue) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-500/10 rounded-lg">
                            <Star class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Menu Terjual</p>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ summary.unique_menus }}
                                <span class="text-sm font-normal text-zinc-500">Jenis</span>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6">
                <div class="flex items-center gap-2 mb-4">
                    <Filter class="h-4 w-4 text-zinc-500" />
                    <h3 class="font-medium text-zinc-900 dark:text-white">Filter Periode</h3>
                </div>

                <!-- Quick Filters -->
                <div class="flex gap-2 mb-4">
                    <Button variant="outline" size="sm" @click="setQuickFilter('today')" class="text-xs">
                        Hari Ini
                    </Button>
                    <Button variant="outline" size="sm" @click="setQuickFilter('week')" class="text-xs">
                        7 Hari Terakhir
                    </Button>
                    <Button variant="outline" size="sm" @click="setQuickFilter('month')" class="text-xs">
                        Bulan Ini
                    </Button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tanggal
                            Mulai</label>
                        <Input v-model="startDate" type="date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tanggal
                            Akhir</label>
                        <Input v-model="endDate" type="date" />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters" class="gap-2 flex-1">
                            <Filter class="h-4 w-4" />
                            Terapkan
                        </Button>
                        <Button @click="resetFilters" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Reset
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Top Menus Table -->
            <div
                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider w-20">
                                    Rank</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Menu</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Kategori</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Qty Terjual</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="menu in topMenus" :key="menu.menu_id"
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                <td class="px-6 py-4 text-center">
                                    <span :class="[
                                        'inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold',
                                        getRankBadge(menu.rank).class
                                    ]">
                                        {{ menu.rank <= 3 ? getRankBadge(menu.rank).icon : menu.rank }} </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center text-lg">
                                            {{ menu.icon }}
                                        </div>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{
                                            menu.menu_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-xs px-2 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                        {{ menu.category_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-bold text-orange-600 dark:text-orange-400">
                                        {{ menu.total_sold }} Porsi
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ formatRupiah(menu.total_revenue) }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="topMenus.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
                                            <Trophy class="h-8 w-8 text-zinc-400" />
                                        </div>
                                        <p class="text-zinc-500 dark:text-zinc-400 mb-1">Belum ada data penjualan</p>
                                        <p class="text-xs text-zinc-400">Coba ubah filter periode untuk melihat data
                                            lainnya</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>

</template>
