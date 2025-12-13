<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import GlassCard from '@/components/ui/GlassCard.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Plus, Pencil, Trash2, MapPin, Users, Store, MoreHorizontal, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useToast } from '@/composables/useToast';

const toast = useToast();

interface BranchData {
    id: number;
    nama: string;
    address: string;
    users_count: number;
    created_at: string;
}

const props = defineProps<{
    branches: BranchData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Cabang',
        href: '/branch',
    },
];

// Search
const searchQuery = ref('');

const filteredBranches = computed(() => {
    if (!searchQuery.value) return props.branches;
    const query = searchQuery.value.toLowerCase();
    return props.branches.filter(
        (branch) =>
            branch.nama.toLowerCase().includes(query) ||
            branch.address.toLowerCase().includes(query)
    );
});

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteDialog = ref(false);
const branchToDelete = ref<BranchData | null>(null);
const branchToEdit = ref<BranchData | null>(null);

// Create form
const createForm = useForm({
    nama: '',
    address: '',
});

// Edit form
const editForm = useForm({
    nama: '',
    address: '',
});

// Create modal handlers
const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post('/branch', {
        onSuccess: () => {
            closeCreateModal();
            toast.success('Berhasil!', 'Cabang baru berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Gagal!', 'Terjadi kesalahan saat menambahkan cabang.');
        },
    });
};

// Edit modal handlers
const openEditModal = (branch: BranchData) => {
    branchToEdit.value = branch;
    editForm.nama = branch.nama;
    editForm.address = branch.address;
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    branchToEdit.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (branchToEdit.value) {
        editForm.put(`/branch/${branchToEdit.value.id}`, {
            onSuccess: () => {
                closeEditModal();
                toast.success('Berhasil!', 'Data cabang berhasil diperbarui.');
            },
            onError: () => {
                toast.error('Gagal!', 'Terjadi kesalahan saat memperbarui data cabang.');
            },
        });
    }
};

// Delete dialog handlers
const openDeleteDialog = (branch: BranchData) => {
    branchToDelete.value = branch;
    showDeleteDialog.value = true;
};

const closeDeleteDialog = () => {
    showDeleteDialog.value = false;
    branchToDelete.value = null;
};

const deleteBranch = () => {
    if (branchToDelete.value) {
        router.delete(`/branch/${branchToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
                toast.success('Berhasil!', 'Cabang berhasil dihapus.');
            },
            onError: () => {
                closeDeleteDialog();
                toast.error('Gagal!', 'Tidak dapat menghapus cabang yang masih memiliki karyawan.');
            },
        });
    }
};
</script>

<template>

    <Head title="Manajemen Cabang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="h-full flex flex-col">
            <!-- Header -->
            <div class="px-6 py-6">
                <div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen Cabang
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Kelola data cabang toko</p>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial">
                            <Search
                                class="absolute left-4 top-1/2 -translate-y-1/2 h-[18px] w-[18px] text-slate-400 dark:text-slate-500" />
                            <input v-model="searchQuery" type="text" placeholder="Cari cabang..."
                                class="pl-11 pr-4 py-3 w-full sm:w-72 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 shadow-sm text-slate-900 dark:text-white placeholder:text-slate-400" />
                        </div>
                        <button
                            class="gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium"
                            @click="openCreateModal">
                            <Plus class="h-4 w-4" />
                            <span class="hidden sm:inline">Tambah Cabang</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Branch Grid Cards -->
                <div v-if="filteredBranches.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="branch in filteredBranches" :key="branch.id" class="group relative overflow-hidden rounded-[2rem] p-6
                               bg-white/60 dark:bg-slate-900/70 backdrop-blur-xl
                               border border-white/40 dark:border-slate-700/50
                               shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]
                               hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgba(0,0,0,0.3)]
                               hover:-translate-y-1 transition-all duration-300">
                        <!-- Inner Gradient -->
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-white/30 to-transparent dark:from-slate-800/20 dark:to-slate-900/10 pointer-events-none" />

                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="h-12 w-12 rounded-xl bg-gradient-to-br from-orange-500 to-amber-400 flex items-center justify-center shadow-lg shadow-orange-500/20">
                                        <Store class="h-6 w-6 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ branch.nama }}
                                        </h3>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 mt-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button
                                        class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors"
                                        @click="openEditModal(branch)">
                                        <Pencil class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                    </button>
                                    <button
                                        class="h-9 w-9 rounded-lg bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 flex items-center justify-center transition-colors"
                                        @click="openDeleteDialog(branch)">
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </button>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div
                                class="flex items-center justify-between p-4 bg-slate-50/80 dark:bg-slate-800/50 rounded-xl mb-4">
                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4 text-slate-400" />
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Total Karyawan</span>
                                </div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ branch.users_count }}
                                    orang</span>
                            </div>

                            <!-- Address -->
                            <div class="flex items-start gap-3">
                                <MapPin class="h-4 w-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2">{{ branch.address }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="flex flex-col items-center justify-center py-16">
                    <div
                        class="h-16 w-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                        <Store class="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">
                        {{ searchQuery ? 'Cabang tidak ditemukan' : 'Belum ada cabang' }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        {{ searchQuery ? 'Coba kata kunci lain' : 'Tambahkan cabang pertama Anda' }}
                    </p>
                    <button v-if="!searchQuery"
                        class="gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium"
                        @click="openCreateModal">
                        <Plus class="h-4 w-4" />
                        Tambah Cabang
                    </button>
                </div>
            </div>

            <!-- Create Modal -->
            <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Tambah Cabang Baru</DialogTitle>
                        <DialogDescription>
                            Isi form berikut untuk menambah cabang baru
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="create-nama">Nama Cabang</Label>
                            <Input id="create-nama" v-model="createForm.nama" type="text"
                                placeholder="Contoh: Cabang Pusat" required />
                            <InputError :message="createForm.errors.nama" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-address">Alamat</Label>
                            <textarea id="create-address" v-model="createForm.address"
                                placeholder="Masukkan alamat lengkap cabang" required rows="3"
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600 resize-none"></textarea>
                            <InputError :message="createForm.errors.address" />
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="closeCreateModal">Batal</Button>
                            <Button type="submit" :disabled="createForm.processing">
                                <Spinner v-if="createForm.processing" class="mr-2" />
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Modal -->
            <Dialog :open="showEditModal" @update:open="showEditModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Cabang</DialogTitle>
                        <DialogDescription>
                            Perbarui data cabang {{ branchToEdit?.nama }}
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-nama">Nama Cabang</Label>
                            <Input id="edit-nama" v-model="editForm.nama" type="text" placeholder="Contoh: Cabang Pusat"
                                required />
                            <InputError :message="editForm.errors.nama" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-address">Alamat</Label>
                            <textarea id="edit-address" v-model="editForm.address"
                                placeholder="Masukkan alamat lengkap cabang" required rows="3"
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600 resize-none"></textarea>
                            <InputError :message="editForm.errors.address" />
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="closeEditModal">Batal</Button>
                            <Button type="submit" :disabled="editForm.processing">
                                <Spinner v-if="editForm.processing" class="mr-2" />
                                Simpan Perubahan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Cabang</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus cabang <strong>{{ branchToDelete?.nama }}</strong>?
                            <span v-if="branchToDelete && branchToDelete.users_count > 0"
                                class="block mt-2 text-red-500">
                                Peringatan: Cabang ini memiliki {{ branchToDelete.users_count }} karyawan yang
                                terdaftar.
                            </span>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="closeDeleteDialog">Batal</Button>
                        <Button variant="destructive" @click="deleteBranch">Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </main>
    </AppLayout>
</template>