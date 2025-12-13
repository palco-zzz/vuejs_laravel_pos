<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import GlassCard from '@/components/ui/GlassCard.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Plus, Pencil, Trash2, Search, Package, Tag, UtensilsCrossed } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useToast } from '@/composables/useToast';

const toast = useToast();

interface CategoryData {
    id: number;
    nama: string;
    icon: string | null;
    menus_count: number;
    created_at: string;
}

interface MenuData {
    id: number;
    category_id: number;
    nama: string;
    harga: number;
    icon: string | null;
    category: CategoryData;
    created_at: string;
}

const props = defineProps<{
    menus: MenuData[];
    categories: CategoryData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Menu',
        href: '/menu',
    },
];

// Tab state
const activeTab = ref<'items' | 'categories'>('items');

// Search & Filter
const searchQuery = ref('');
const filterCategory = ref('');

const filteredMenus = computed(() => {
    let result = props.menus;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((menu) =>
            menu.nama.toLowerCase().includes(query)
        );
    }

    if (filterCategory.value) {
        result = result.filter((menu) =>
            menu.category_id === parseInt(filterCategory.value)
        );
    }

    return result;
});

const filteredCategories = computed(() => {
    if (!searchQuery.value) return props.categories;
    const query = searchQuery.value.toLowerCase();
    return props.categories.filter((cat) =>
        cat.nama.toLowerCase().includes(query)
    );
});

// Format currency
const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

// ==================== MENU MODALS ====================
const showMenuCreateModal = ref(false);
const showMenuEditModal = ref(false);
const showMenuDeleteDialog = ref(false);
const menuToEdit = ref<MenuData | null>(null);
const menuToDelete = ref<MenuData | null>(null);

const menuCreateForm = useForm({
    category_id: '',
    nama: '',
    harga: 0,
    icon: '🍞',
});

const menuEditForm = useForm({
    category_id: '',
    nama: '',
    harga: 0,
    icon: '',
});

// Menu Create
const openMenuCreateModal = () => {
    menuCreateForm.reset();
    menuCreateForm.icon = '🍞';
    menuCreateForm.clearErrors();
    showMenuCreateModal.value = true;
};

const closeMenuCreateModal = () => {
    showMenuCreateModal.value = false;
    menuCreateForm.reset();
    menuCreateForm.clearErrors();
};

const submitMenuCreate = () => {
    menuCreateForm.post('/menu', {
        onSuccess: () => {
            closeMenuCreateModal();
            toast.success('Berhasil!', 'Menu baru berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Gagal!', 'Terjadi kesalahan saat menambahkan menu.');
        },
    });
};

// Menu Edit
const openMenuEditModal = (menu: MenuData) => {
    menuToEdit.value = menu;
    menuEditForm.category_id = menu.category_id.toString();
    menuEditForm.nama = menu.nama;
    menuEditForm.harga = menu.harga;
    menuEditForm.icon = menu.icon || '🍞';
    menuEditForm.clearErrors();
    showMenuEditModal.value = true;
};

const closeMenuEditModal = () => {
    showMenuEditModal.value = false;
    menuToEdit.value = null;
    menuEditForm.reset();
    menuEditForm.clearErrors();
};

const submitMenuEdit = () => {
    if (menuToEdit.value) {
        menuEditForm.put(`/menu/${menuToEdit.value.id}`, {
            onSuccess: () => {
                closeMenuEditModal();
                toast.success('Berhasil!', 'Menu berhasil diperbarui.');
            },
            onError: () => {
                toast.error('Gagal!', 'Terjadi kesalahan saat memperbarui menu.');
            },
        });
    }
};

// Menu Delete
const openMenuDeleteDialog = (menu: MenuData) => {
    menuToDelete.value = menu;
    showMenuDeleteDialog.value = true;
};

const closeMenuDeleteDialog = () => {
    showMenuDeleteDialog.value = false;
    menuToDelete.value = null;
};

const deleteMenu = () => {
    if (menuToDelete.value) {
        router.delete(`/menu/${menuToDelete.value.id}`, {
            onSuccess: () => {
                closeMenuDeleteDialog();
                toast.success('Berhasil!', 'Menu berhasil dihapus.');
            },
            onError: () => {
                closeMenuDeleteDialog();
                toast.error('Gagal!', 'Terjadi kesalahan saat menghapus menu.');
            },
        });
    }
};

// ==================== CATEGORY MODALS ====================
const showCategoryCreateModal = ref(false);
const showCategoryEditModal = ref(false);
const showCategoryDeleteDialog = ref(false);
const categoryToEdit = ref<CategoryData | null>(null);
const categoryToDelete = ref<CategoryData | null>(null);

const categoryCreateForm = useForm({
    nama: '',
    icon: '🍞',
});

const categoryEditForm = useForm({
    nama: '',
    icon: '',
});

// Category Create
const openCategoryCreateModal = () => {
    categoryCreateForm.reset();
    categoryCreateForm.icon = '🍞';
    categoryCreateForm.clearErrors();
    showCategoryCreateModal.value = true;
};

const closeCategoryCreateModal = () => {
    showCategoryCreateModal.value = false;
    categoryCreateForm.reset();
    categoryCreateForm.clearErrors();
};

const submitCategoryCreate = () => {
    categoryCreateForm.post('/category', {
        onSuccess: () => {
            closeCategoryCreateModal();
            toast.success('Berhasil!', 'Kategori baru berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Gagal!', 'Terjadi kesalahan saat menambahkan kategori.');
        },
    });
};

// Category Edit
const openCategoryEditModal = (category: CategoryData) => {
    categoryToEdit.value = category;
    categoryEditForm.nama = category.nama;
    categoryEditForm.icon = category.icon || '🍞';
    categoryEditForm.clearErrors();
    showCategoryEditModal.value = true;
};

const closeCategoryEditModal = () => {
    showCategoryEditModal.value = false;
    categoryToEdit.value = null;
    categoryEditForm.reset();
    categoryEditForm.clearErrors();
};

const submitCategoryEdit = () => {
    if (categoryToEdit.value) {
        categoryEditForm.put(`/category/${categoryToEdit.value.id}`, {
            onSuccess: () => {
                closeCategoryEditModal();
                toast.success('Berhasil!', 'Kategori berhasil diperbarui.');
            },
            onError: () => {
                toast.error('Gagal!', 'Terjadi kesalahan saat memperbarui kategori.');
            },
        });
    }
};

// Category Delete
const openCategoryDeleteDialog = (category: CategoryData) => {
    categoryToDelete.value = category;
    showCategoryDeleteDialog.value = true;
};

const closeCategoryDeleteDialog = () => {
    showCategoryDeleteDialog.value = false;
    categoryToDelete.value = null;
};

const deleteCategory = () => {
    if (categoryToDelete.value) {
        router.delete(`/category/${categoryToDelete.value.id}`, {
            onSuccess: () => {
                closeCategoryDeleteDialog();
                toast.success('Berhasil!', 'Kategori berhasil dihapus.');
            },
            onError: () => {
                closeCategoryDeleteDialog();
                toast.error('Gagal!', 'Tidak dapat menghapus kategori yang masih memiliki menu.');
            },
        });
    }
};

// Common emoji icons for food
const foodEmojis = ['🍞', '🥐', '🧀', '🍫', '🥜', '🍓', '🥭', '🍌', '☕', '🧋', '🍵', '🥛'];
</script>

<template>

    <Head title="Manajemen Menu" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="h-full flex flex-col">
            <!-- Header -->
            <div class="px-6 py-6">
                <div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Manajemen Menu
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">
                            Kelola menu dan kategori produk
                        </p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-4 border-b border-slate-100 dark:border-slate-800">
                    <button @click="activeTab = 'items'" :class="activeTab === 'items'
                        ? 'text-orange-600 dark:text-orange-500 border-orange-500'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border-transparent'
                        " class="pb-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2">
                        <UtensilsCrossed class="h-4 w-4" />
                        Daftar Menu
                    </button>
                    <button @click="activeTab = 'categories'" :class="activeTab === 'categories'
                        ? 'text-orange-600 dark:text-orange-500 border-orange-500'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border-transparent'
                        " class="pb-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2">
                        <Tag class="h-4 w-4" />
                        Kategori Menu
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- ==================== MENU LIST TAB ==================== -->
                <div v-if="activeTab === 'items'" class="space-y-6">
                    <!-- Search & Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-auto">
                                <Search
                                    class="absolute left-4 top-1/2 -translate-y-1/2 h-[18px] w-[18px] text-slate-400 dark:text-slate-500" />
                                <input v-model="searchQuery" type="text" placeholder="Cari menu..."
                                    class="pl-11 pr-4 py-3 w-full sm:w-72 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 shadow-sm text-slate-900 dark:text-white placeholder:text-slate-400" />
                            </div>
                            <select v-model="filterCategory"
                                class="w-full sm:w-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 shadow-sm">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.icon }} {{ cat.nama }}
                                </option>
                            </select>
                        </div>
                        <button
                            class="w-full sm:w-auto gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium"
                            @click="openMenuCreateModal">
                            <Plus class="h-4 w-4" />
                            <span>Tambah Menu</span>
                        </button>
                    </div>

                    <!-- Menu Table (Desktop) -->
                    <GlassCard noPadding class="hidden md:block overflow-hidden">
                        <table class="w-full text-left">
                            <thead
                                class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                                <tr
                                    class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">
                                    <th class="p-5">Gambar</th>
                                    <th class="p-5">Nama Menu</th>
                                    <th class="p-5">Kategori</th>
                                    <th class="p-5 text-right">Harga</th>
                                    <th class="p-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <tr v-for="menu in filteredMenus" :key="menu.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="p-5">
                                        <div
                                            class="h-12 w-12 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-2xl shadow-sm">
                                            {{ menu.icon || "🍞" }}
                                        </div>
                                    </td>
                                    <td class="p-5">
                                        <div class="flex flex-col">
                                            <span class="text-slate-900 dark:text-white font-medium">{{
                                                menu.nama
                                            }}</span>
                                            <span class="text-xs text-slate-400 dark:text-slate-500">ID: #MENU-{{
                                                menu.id }}</span>
                                        </div>
                                    </td>
                                    <td class="p-5">
                                        <span
                                            class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-lg text-xs font-medium">
                                            {{ menu.category?.icon }} {{ menu.category?.nama }}
                                        </span>
                                    </td>
                                    <td class="p-5 text-right font-bold text-slate-900 dark:text-white">
                                        {{ formatRupiah(menu.harga) }}
                                    </td>
                                    <td class="p-5 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors"
                                                @click="openMenuEditModal(menu)">
                                                <Pencil class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                            </button>
                                            <button
                                                class="h-9 w-9 rounded-lg bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 flex items-center justify-center transition-colors"
                                                @click="openMenuDeleteDialog(menu)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredMenus.length === 0">
                                    <td colspan="5" class="p-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                                                <Package class="h-7 w-7 text-slate-400" />
                                            </div>
                                            <p class="text-slate-500 dark:text-slate-400 font-medium">Tidak ada menu
                                                yang ditemukan.</p>
                                            <button v-if="!searchQuery && !filterCategory"
                                                class="mt-4 px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center"
                                                @click="openMenuCreateModal">
                                                <Plus class="h-4 w-4 mr-2" />
                                                Tambah Menu Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </GlassCard>

                    <!-- Menu Cards (Mobile) -->
                    <div class="md:hidden space-y-4">
                        <div v-for="menu in filteredMenus" :key="menu.id"
                            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div class="flex gap-3">
                                    <div
                                        class="h-12 w-12 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center text-2xl shrink-0 border border-zinc-200 dark:border-zinc-700">
                                        {{ menu.icon || "🍞" }}
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-zinc-900 dark:text-white">
                                            {{ menu.nama }}
                                        </h3>
                                        <p class="text-xs text-zinc-500 mb-1">ID: #MENU-{{ menu.id }}</p>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                            {{ menu.category?.icon }} {{ menu.category?.nama }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                                <div class="flex flex-col">
                                    <span class="text-xs text-zinc-500">Harga</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-200">{{
                                        formatRupiah(menu.harga)
                                    }}</span>
                                </div>
                            </div>

                            <div class="mt-3 flex gap-2">
                                <Button variant="outline" size="sm" class="flex-1" @click="openMenuEditModal(menu)">
                                    <Pencil class="h-4 w-4 mr-2" /> Edit
                                </Button>
                                <Button variant="outline" size="sm"
                                    class="flex-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 border-red-200 dark:border-red-900/30"
                                    @click="openMenuDeleteDialog(menu)">
                                    <Trash2 class="h-4 w-4 mr-2" /> Hapus
                                </Button>
                            </div>
                        </div>

                        <!-- Empty State Mobile -->
                        <div v-if="filteredMenus.length === 0"
                            class="text-center py-12 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <div class="flex flex-col items-center">
                                <div
                                    class="h-12 w-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
                                    <Package class="h-6 w-6 text-zinc-400" />
                                </div>
                                <p class="text-zinc-500">Tidak ada menu yang ditemukan.</p>
                                <Button v-if="!searchQuery && !filterCategory" variant="outline" size="sm" class="mt-3"
                                    @click="openMenuCreateModal">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Tambah Menu Pertama
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== CATEGORY LIST TAB ==================== -->
                <div v-if="activeTab === 'categories'" class="space-y-6">
                    <!-- Header & Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Kategori Menu
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Kelola kategori untuk pengelompokan produk.
                            </p>
                        </div>
                        <button
                            class="w-full sm:w-auto gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium"
                            @click="openCategoryCreateModal">
                            <Plus class="h-4 w-4" />
                            <span>Tambah Kategori</span>
                        </button>
                    </div>

                    <!-- Category Table (Desktop) -->
                    <GlassCard noPadding class="hidden md:block overflow-hidden">
                        <table class="w-full text-left">
                            <thead
                                class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                                <tr
                                    class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">
                                    <th class="p-5 w-16">Ikon</th>
                                    <th class="p-5">Nama Kategori</th>
                                    <th class="p-5 text-center">Jumlah Menu</th>
                                    <th class="p-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <tr v-for="category in categories" :key="category.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="p-5">
                                        <div
                                            class="h-12 w-12 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-2xl">
                                            {{ category.icon || "🍞" }}
                                        </div>
                                    </td>
                                    <td class="p-5 font-bold text-slate-900 dark:text-white">
                                        {{ category.nama }}
                                    </td>
                                    <td class="p-5 text-center">
                                        <span
                                            class="px-3 py-1.5 bg-orange-100 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-lg text-xs font-bold">
                                            {{ category.menus_count }} menu
                                        </span>
                                    </td>
                                    <td class="p-5 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors"
                                                @click="openCategoryEditModal(category)">
                                                <Pencil class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                            </button>
                                            <button
                                                class="h-9 w-9 rounded-lg bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 flex items-center justify-center transition-colors"
                                                @click="openCategoryDeleteDialog(category)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="4" class="p-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                                                <Tag class="h-7 w-7 text-slate-400" />
                                            </div>
                                            <p class="text-slate-500 dark:text-slate-400 font-medium">Belum ada
                                                kategori.</p>
                                            <button
                                                class="mt-4 px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center"
                                                @click="openCategoryCreateModal">
                                                <Plus class="h-4 w-4 mr-2" />
                                                Tambah Kategori Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </GlassCard>

                    <!-- Category Cards (Mobile) -->
                    <div class="md:hidden space-y-4">
                        <div v-for="category in categories" :key="category.id"
                            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-12 w-12 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center text-2xl shrink-0 border border-zinc-200 dark:border-zinc-700">
                                        {{ category.icon || "🍞" }}
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-zinc-900 dark:text-white">
                                            {{ category.nama }}
                                        </h3>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 mt-1">
                                            {{ category.menus_count }} menu
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-2">
                                <Button variant="outline" size="sm" class="flex-1"
                                    @click="openCategoryEditModal(category)">
                                    <Pencil class="h-4 w-4 mr-2" /> Edit
                                </Button>
                                <Button variant="outline" size="sm"
                                    class="flex-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 border-red-200 dark:border-red-900/30"
                                    @click="openCategoryDeleteDialog(category)">
                                    <Trash2 class="h-4 w-4 mr-2" /> Hapus
                                </Button>
                            </div>
                        </div>

                        <!-- Empty State Mobile -->
                        <div v-if="categories.length === 0"
                            class="text-center py-12 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <div class="flex flex-col items-center">
                                <div
                                    class="h-12 w-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
                                    <Tag class="h-6 w-6 text-zinc-400" />
                                </div>
                                <p class="text-zinc-500">Belum ada kategori.</p>
                                <Button variant="outline" size="sm" class="mt-3" @click="openCategoryCreateModal">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Tambah Kategori Pertama
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== MENU MODALS ==================== -->

            <!-- Create Menu Modal -->
            <Dialog :open="showMenuCreateModal" @update:open="showMenuCreateModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Tambah Menu Baru</DialogTitle>
                        <DialogDescription>
                            Isi form berikut untuk menambah menu baru
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitMenuCreate" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="menu-category">Kategori</Label>
                            <select id="menu-category" v-model="menuCreateForm.category_id" required
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600">
                                <option value="">Pilih Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.icon }} {{ cat.nama }}
                                </option>
                            </select>
                            <InputError :message="menuCreateForm.errors.category_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="menu-nama">Nama Menu</Label>
                            <Input id="menu-nama" v-model="menuCreateForm.nama" type="text"
                                placeholder="Contoh: Ovomaltine - Selay" required />
                            <InputError :message="menuCreateForm.errors.nama" />
                        </div>

                        <div class="space-y-2">
                            <Label for="menu-harga">Harga (Rp)</Label>
                            <Input id="menu-harga" v-model.number="menuCreateForm.harga" type="number" min="0"
                                placeholder="25000" required />
                            <InputError :message="menuCreateForm.errors.harga" />
                        </div>

                        <div class="space-y-2">
                            <Label>Ikon</Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="emoji in foodEmojis" :key="emoji" type="button"
                                    @click="menuCreateForm.icon = emoji" :class="menuCreateForm.icon === emoji
                                        ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-500/10'
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        "
                                    class="h-10 w-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-lg transition-all">
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="closeMenuCreateModal">Batal</Button>
                            <Button type="submit" :disabled="menuCreateForm.processing">
                                <Spinner v-if="menuCreateForm.processing" class="mr-2" />
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Menu Modal -->
            <Dialog :open="showMenuEditModal" @update:open="showMenuEditModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Menu</DialogTitle>
                        <DialogDescription>
                            Perbarui data menu {{ menuToEdit?.nama }}
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitMenuEdit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-menu-category">Kategori</Label>
                            <select id="edit-menu-category" v-model="menuEditForm.category_id" required
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600">
                                <option value="">Pilih Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.icon }} {{ cat.nama }}
                                </option>
                            </select>
                            <InputError :message="menuEditForm.errors.category_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-menu-nama">Nama Menu</Label>
                            <Input id="edit-menu-nama" v-model="menuEditForm.nama" type="text"
                                placeholder="Contoh: Ovomaltine - Selay" required />
                            <InputError :message="menuEditForm.errors.nama" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-menu-harga">Harga (Rp)</Label>
                            <Input id="edit-menu-harga" v-model.number="menuEditForm.harga" type="number" min="0"
                                placeholder="25000" required />
                            <InputError :message="menuEditForm.errors.harga" />
                        </div>

                        <div class="space-y-2">
                            <Label>Ikon</Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="emoji in foodEmojis" :key="emoji" type="button"
                                    @click="menuEditForm.icon = emoji" :class="menuEditForm.icon === emoji
                                        ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-500/10'
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        "
                                    class="h-10 w-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-lg transition-all">
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Ikon</Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="emoji in foodEmojis" :key="emoji" type="button"
                                    @click="menuEditForm.icon = emoji" :class="menuEditForm.icon === emoji
                                        ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-500/10'
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        "
                                    class="h-10 w-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-lg transition-all">
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="closeMenuEditModal">Batal</Button>
                            <Button type="submit" :disabled="menuEditForm.processing">
                                <Spinner v-if="menuEditForm.processing" class="mr-2" />
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Delete Menu Dialog -->
            <Dialog :open="showMenuDeleteDialog" @update:open="showMenuDeleteDialog = $event">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Menu</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus menu
                            <strong>{{ menuToDelete?.nama }}</strong>? Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="closeMenuDeleteDialog">Batal</Button>
                        <Button variant="destructive" @click="deleteMenu">Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- ==================== CATEGORY MODALS ==================== -->

            <!-- Create Category Modal -->
            <Dialog :open="showCategoryCreateModal" @update:open="showCategoryCreateModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Tambah Kategori Baru</DialogTitle>
                        <DialogDescription>
                            Isi form berikut untuk menambah kategori baru
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCategoryCreate" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="category-nama">Nama Kategori</Label>
                            <Input id="category-nama" v-model="categoryCreateForm.nama" type="text"
                                placeholder="Contoh: Aneka Ovomaltine" required />
                            <InputError :message="categoryCreateForm.errors.nama" />
                        </div>

                        <div class="space-y-2">
                            <Label>Ikon</Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="emoji in foodEmojis" :key="emoji" type="button"
                                    @click="categoryCreateForm.icon = emoji" :class="categoryCreateForm.icon === emoji
                                        ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-500/10'
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        "
                                    class="h-10 w-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-lg transition-all">
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="closeCategoryCreateModal">Batal</Button>
                            <Button type="submit" :disabled="categoryCreateForm.processing">
                                <Spinner v-if="categoryCreateForm.processing" class="mr-2" />
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Category Modal -->
            <Dialog :open="showCategoryEditModal" @update:open="showCategoryEditModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Kategori</DialogTitle>
                        <DialogDescription>
                            Perbarui data kategori {{ categoryToEdit?.nama }}
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCategoryEdit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-category-nama">Nama Kategori</Label>
                            <Input id="edit-category-nama" v-model="categoryEditForm.nama" type="text"
                                placeholder="Contoh: Aneka Ovomaltine" required />
                            <InputError :message="categoryEditForm.errors.nama" />
                        </div>

                        <div class="space-y-2">
                            <Label>Ikon</Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="emoji in foodEmojis" :key="emoji" type="button"
                                    @click="categoryEditForm.icon = emoji" :class="categoryEditForm.icon === emoji
                                        ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-500/10'
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        "
                                    class="h-10 w-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-lg transition-all">
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="closeCategoryEditModal">Batal</Button>
                            <Button type="submit" :disabled="categoryEditForm.processing">
                                <Spinner v-if="categoryEditForm.processing" class="mr-2" />
                                Simpan Perubahan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Delete Category Dialog -->
            <Dialog :open="showCategoryDeleteDialog" @update:open="showCategoryDeleteDialog = $event">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Kategori</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus kategori
                            <strong>{{ categoryToDelete?.nama }}</strong>?
                            <span v-if="categoryToDelete && categoryToDelete.menus_count > 0"
                                class="block mt-2 text-red-500">
                                Peringatan: Kategori ini memiliki {{ categoryToDelete.menus_count }} menu
                                yang terdaftar.
                            </span>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="closeCategoryDeleteDialog">Batal</Button>
                        <Button variant="destructive" @click="deleteCategory">Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </main>
    </AppLayout>
</template>
