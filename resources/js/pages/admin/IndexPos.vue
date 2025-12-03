<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pos',
        href: '/pos',
    },
];

// State sederhana untuk demo perpindahan tab antara POS dan Inventory
const activeTab = ref('pos');
</script>

<template>

    <Head title="Point of Sales" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Main Content Wrapper -->
        <main class="h-full flex flex-col bg-zinc-50 dark:bg-black transition-colors duration-300">

            <!-- Tab Switcher (Optional: Added for demo purposes to switch views) -->
            <div class="px-6 pt-4 pb-2 flex gap-4 border-b border-zinc-200 dark:border-zinc-800">
                <button @click="activeTab = 'pos'"
                    :class="activeTab === 'pos' ? 'text-orange-500 border-b-2 border-orange-500' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                    class="pb-2 text-sm font-medium transition-colors">
                    Point of Sales
                </button>
                <button @click="activeTab = 'inventory'"
                    :class="activeTab === 'inventory' ? 'text-orange-500 border-b-2 border-orange-500' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                    class="pb-2 text-sm font-medium transition-colors">
                    Inventory
                </button>
            </div>

            <div class="flex-1 overflow-hidden p-6 relative">

                <!-- VIEW: POS -->
                <div v-if="activeTab === 'pos'" id="view-pos" class="h-full flex flex-col md:flex-row gap-6 fade-in">
                    <!-- Product Grid -->
                    <div class="flex-1 flex flex-col h-full overflow-hidden">
                        <!-- Category Filters -->
                        <div class="flex gap-2 mb-6 overflow-x-auto pb-2 no-scrollbar">
                            <button
                                class="bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap shadow-sm">
                                Semua Menu
                            </button>
                            <button
                                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:text-white px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-colors shadow-sm dark:shadow-none">
                                Roti Manis
                            </button>
                            <button
                                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:text-white px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-colors shadow-sm dark:shadow-none">
                                Roti Asin
                            </button>
                            <button
                                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:text-white px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-colors shadow-sm dark:shadow-none">
                                Minuman
                            </button>
                        </div>

                        <div
                            class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 overflow-y-auto pb-20 md:pb-0 pr-2 custom-scrollbar">
                            <!-- Product Card 1 -->
                            <div
                                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-4 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-800/40 cursor-pointer transition-all group shadow-sm dark:shadow-none">
                                <div
                                    class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-3 flex items-center justify-center relative overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-tr from-orange-500/10 dark:from-orange-900/20 to-transparent">
                                    </div>
                                    <span class="text-4xl drop-shadow-sm">🍞</span>
                                </div>
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Coklat Keju</h4>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs text-zinc-500">Stok: 12</span>
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">Rp
                                        22k</span>
                                </div>
                            </div>
                            <!-- Product Card 2 -->
                            <div
                                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-4 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-800/40 cursor-pointer transition-all group shadow-sm dark:shadow-none">
                                <div
                                    class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-3 flex items-center justify-center relative overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-tr from-yellow-500/10 dark:from-yellow-900/20 to-transparent">
                                    </div>
                                    <span class="text-4xl drop-shadow-sm">🧀</span>
                                </div>
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Keju Susu</h4>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs text-zinc-500">Stok: 24</span>
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">Rp
                                        18k</span>
                                </div>
                            </div>
                            <!-- Product Card 3 -->
                            <div
                                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-4 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-800/40 cursor-pointer transition-all group shadow-sm dark:shadow-none">
                                <div
                                    class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-3 flex items-center justify-center relative overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-tr from-green-500/10 dark:from-green-900/20 to-transparent">
                                    </div>
                                    <span class="text-4xl drop-shadow-sm">🧇</span>
                                </div>
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Srikaya Pandan</h4>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs text-zinc-500">Stok: 8</span>
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">Rp
                                        20k</span>
                                </div>
                            </div>
                            <!-- Product Card 4 -->
                            <div
                                class="bg-white dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800/60 p-4 rounded-xl hover:border-zinc-300 dark:hover:bg-zinc-800/40 cursor-pointer transition-all group shadow-sm dark:shadow-none">
                                <div
                                    class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-3 flex items-center justify-center relative overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-tr from-red-500/10 dark:from-red-900/20 to-transparent">
                                    </div>
                                    <span class="text-4xl drop-shadow-sm">🥩</span>
                                </div>
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-200">Daging Telur</h4>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs text-zinc-500">Stok: 15</span>
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">Rp
                                        28k</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Panel -->
                    <div
                        class="w-full md:w-80 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col h-[calc(100vh-10rem)] shadow-sm dark:shadow-none">
                        <div class="p-4 border-b border-zinc-200 dark:border-zinc-800">
                            <h3 class="font-medium text-zinc-900 dark:text-zinc-200">Pesanan Baru</h3>
                            <span class="text-xs text-zinc-500">Order #2940</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                            <!-- Cart Item 1 -->
                            <div class="flex justify-between items-start group">
                                <div class="flex gap-3">
                                    <div
                                        class="h-10 w-10 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center text-lg">
                                        🍞</div>
                                    <div>
                                        <p class="text-sm text-zinc-900 dark:text-zinc-200 font-medium">Coklat Keju</p>
                                        <p class="text-xs text-zinc-500">1 x Rp 22.000</p>
                                    </div>
                                </div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">Rp 22k</p>
                            </div>
                            <!-- Cart Item 2 -->
                            <div class="flex justify-between items-start group">
                                <div class="flex gap-3">
                                    <div
                                        class="h-10 w-10 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center text-lg">
                                        🥤</div>
                                    <div>
                                        <p class="text-sm text-zinc-900 dark:text-zinc-200 font-medium">Es Teh Manis</p>
                                        <p class="text-xs text-zinc-500">2 x Rp 5.000</p>
                                    </div>
                                </div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">Rp 10k</p>
                            </div>
                        </div>
                        <div
                            class="p-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 rounded-b-xl">
                            <div class="flex justify-between mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span>Rp 32.000</span>
                            </div>
                            <div class="flex justify-between mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                                <span>Pajak (10%)</span>
                                <span>Rp 3.200</span>
                            </div>
                            <div
                                class="flex justify-between mb-6 text-base font-semibold text-zinc-900 dark:text-white">
                                <span>Total</span>
                                <span>Rp 35.200</span>
                            </div>
                            <button
                                class="w-full bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-200 text-white dark:text-zinc-950 font-medium py-3 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <i data-lucide="printer" class="h-4 w-4"></i> Proses &amp; Cetak
                            </button>
                        </div>
                    </div>
                </div>

                <!-- VIEW: INVENTORY -->
                <div v-if="activeTab === 'inventory'" id="view-inventory"
                    class="h-full space-y-6 fade-in flex flex-col">
                    <div class="flex justify-between items-center">
                        <div class="flex gap-2">
                            <input type="text" placeholder="Cari bahan baku..."
                                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg px-4 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-600 w-64 shadow-sm dark:shadow-none placeholder:text-zinc-400">
                            <button
                                class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 px-4 py-2 rounded-lg text-sm border border-zinc-200 dark:border-zinc-700 shadow-sm dark:shadow-none"><i
                                    data-lucide="filter" class="h-4 w-4"></i></button>
                        </div>
                        <button
                            class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
                            <i data-lucide="plus" class="h-4 w-4"></i> Tambah Stok
                        </button>
                    </div>

                    <div
                        class="bg-white dark:bg-zinc-900/30 border border-zinc-200 dark:border-zinc-800/60 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                                <tr class="text-xs text-zinc-500 uppercase tracking-wider">
                                    <th class="py-3 px-6 font-medium">Nama Bahan</th>
                                    <th class="py-3 px-6 font-medium">Kategori</th>
                                    <th class="py-3 px-6 font-medium">Stok Saat Ini</th>
                                    <th class="py-3 px-6 font-medium">Unit</th>
                                    <th class="py-3 px-6 font-medium text-center">Status</th>
                                    <th class="py-3 px-6 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800/50">
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                    <td class="py-4 px-6 text-zinc-900 dark:text-zinc-200 font-medium">Roti Tawar
                                        Premium</td>
                                    <td class="py-4 px-6 text-zinc-500">Bahan Utama</td>
                                    <td class="py-4 px-6 text-zinc-700 dark:text-zinc-300">45</td>
                                    <td class="py-4 px-6 text-zinc-500">Pack</td>
                                    <td class="py-4 px-6 text-center"><span
                                            class="bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-500 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:border-green-500/20">Aman</span>
                                    </td>
                                    <td class="py-4 px-6 text-right"><i data-lucide="more-horizontal"
                                            class="h-4 w-4 text-zinc-400 dark:text-zinc-500 inline cursor-pointer hover:text-zinc-900 dark:hover:text-zinc-300"></i>
                                    </td>
                                </tr>
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                    <td class="py-4 px-6 text-zinc-900 dark:text-zinc-200 font-medium">Selai Coklat</td>
                                    <td class="py-4 px-6 text-zinc-500">Topping</td>
                                    <td class="py-4 px-6 text-red-600 dark:text-red-400 font-medium">2.1</td>
                                    <td class="py-4 px-6 text-zinc-500">Kg</td>
                                    <td class="py-4 px-6 text-center"><span
                                            class="bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-500 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:border-red-500/20">Kritis</span>
                                    </td>
                                    <td class="py-4 px-6 text-right"><i data-lucide="more-horizontal"
                                            class="h-4 w-4 text-zinc-400 dark:text-zinc-500 inline cursor-pointer hover:text-zinc-900 dark:hover:text-zinc-300"></i>
                                    </td>
                                </tr>
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-colors">
                                    <td class="py-4 px-6 text-zinc-900 dark:text-zinc-200 font-medium">Keju Mozzarella
                                    </td>
                                    <td class="py-4 px-6 text-zinc-500">Topping</td>
                                    <td class="py-4 px-6 text-orange-600 dark:text-orange-400 font-medium">4.5</td>
                                    <td class="py-4 px-6 text-zinc-500">Kg</td>
                                    <td class="py-4 px-6 text-center"><span
                                            class="bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-500 px-2 py-1 rounded text-xs font-medium border border-orange-200 dark:border-orange-500/20">Warning</span>
                                    </td>
                                    <td class="py-4 px-6 text-right"><i data-lucide="more-horizontal"
                                            class="h-4 w-4 text-zinc-400 dark:text-zinc-500 inline cursor-pointer hover:text-zinc-900 dark:hover:text-zinc-300"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </AppLayout>
</template>