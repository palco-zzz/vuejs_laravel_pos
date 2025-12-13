<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import GlassCard from '@/components/ui/GlassCard.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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
import { Plus, Pencil, Trash2, User, MapPin } from 'lucide-vue-next';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';

const toast = useToast();

interface BranchData {
    id: number;
    nama: string;
    address: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'cashier';
    branch_id: number | null;
    branch: BranchData | null;
    created_at: string;
}

const props = defineProps<{
    users: UserData[];
    branches: BranchData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Karyawan',
        href: '/karyawan',
    },
];

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteDialog = ref(false);
const userToDelete = ref<UserData | null>(null);
const userToEdit = ref<UserData | null>(null);

// Create form
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'cashier' as 'admin' | 'cashier',
    branch_id: null as number | null,
});

// Edit form
const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'cashier' as 'admin' | 'cashier',
    branch_id: null as number | null,
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
    createForm.post('/karyawan', {
        onSuccess: () => {
            closeCreateModal();
            toast.success('Berhasil!', 'Karyawan baru berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Gagal!', 'Terjadi kesalahan saat menambahkan karyawan.');
        },
    });
};

// Edit modal handlers
const openEditModal = (user: UserData) => {
    userToEdit.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.role = user.role;
    editForm.branch_id = user.branch_id;
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    userToEdit.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (userToEdit.value) {
        editForm.put(`/karyawan/${userToEdit.value.id}`, {
            onSuccess: () => {
                closeEditModal();
                toast.success('Berhasil!', 'Data karyawan berhasil diperbarui.');
            },
            onError: () => {
                toast.error('Gagal!', 'Terjadi kesalahan saat memperbarui data karyawan.');
            },
        });
    }
};

// Delete dialog handlers
const openDeleteDialog = (user: UserData) => {
    userToDelete.value = user;
    showDeleteDialog.value = true;
};

const closeDeleteDialog = () => {
    showDeleteDialog.value = false;
    userToDelete.value = null;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(`/karyawan/${userToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteDialog();
                toast.success('Berhasil!', 'Karyawan berhasil dihapus.');
            },
            onError: () => {
                closeDeleteDialog();
                toast.error('Gagal!', 'Terjadi kesalahan saat menghapus karyawan.');
            },
        });
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>

    <Head title="Karyawan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="h-full flex flex-col">
            <!-- Header -->
            <div class="px-6 py-6">
                <div class="flex flex-col md:flex-row justify-between md:items-end gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen Karyawan
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Kelola data karyawan dan akun pengguna</p>
                    </div>
                    <button
                        class="w-full sm:w-auto gap-2 px-5 py-3 bg-slate-900 dark:bg-orange-600 text-white rounded-xl shadow-lg hover:bg-slate-800 dark:hover:bg-orange-500 transition-all flex items-center justify-center font-medium"
                        @click="openCreateModal">
                        <Plus class="h-4 w-4" />
                        Tambah Karyawan
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Desktop View -->
                <GlassCard noPadding class="hidden md:block overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Daftar Karyawan</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead
                                class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                                <tr
                                    class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">
                                    <th class="p-5">Nama</th>
                                    <th class="p-5">Email</th>
                                    <th class="p-5">Role</th>
                                    <th class="p-5">Cabang</th>
                                    <th class="p-5">Tanggal Dibuat</th>
                                    <th class="p-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <tr v-for="user in users" :key="user.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="p-5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/20">
                                                <User class="h-5 w-5 text-white" />
                                            </div>
                                            <span class="font-bold text-slate-900 dark:text-white">{{ user.name
                                                }}</span>
                                        </div>
                                    </td>
                                    <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ user.email }}</td>
                                    <td class="p-5">
                                        <span :class="user.role === 'admin'
                                            ? 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold">
                                            {{ user.role === 'admin' ? 'Admin' : 'Cashier' }}
                                        </span>
                                    </td>
                                    <td class="p-5">
                                        <div v-if="user.branch"
                                            class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
                                            <MapPin class="h-3.5 w-3.5 text-slate-400" />
                                            {{ user.branch.nama }}
                                        </div>
                                        <span v-else class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                    </td>
                                    <td class="p-5 text-sm text-slate-500 dark:text-slate-400">{{
                                        formatDate(user.created_at) }}</td>
                                    <td class="p-5">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors"
                                                @click="openEditModal(user)">
                                                <Pencil class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                                            </button>
                                            <button
                                                class="h-9 w-9 rounded-lg bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 flex items-center justify-center transition-colors"
                                                @click="openDeleteDialog(user)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="6" class="p-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                                                <User class="h-7 w-7 text-slate-400" />
                                            </div>
                                            <p class="text-slate-500 dark:text-slate-400 font-medium">Belum ada data
                                                karyawan</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </GlassCard>

                <!-- Mobile View -->
                <div class="md:hidden space-y-4">
                    <div v-for="user in users" :key="user.id"
                        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <User class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                                </div>
                                <div>
                                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ user.name }}</h3>
                                    <p class="text-xs text-zinc-500">{{ user.email }}</p>
                                </div>
                            </div>
                            <Badge :variant="user.role === 'admin' ? 'default' : 'secondary'">
                                {{ user.role === 'admin' ? 'Admin' : 'Cashier' }}
                            </Badge>
                        </div>

                        <div class="space-y-2 text-sm border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Cabang</span>
                                <span class="text-zinc-900 dark:text-zinc-200 flex items-center gap-1">
                                    <MapPin v-if="user.branch" class="h-3.5 w-3.5 text-zinc-400" />
                                    {{ user.branch ? user.branch.nama : '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Bergabung</span>
                                <span class="text-zinc-900 dark:text-zinc-200">{{ formatDate(user.created_at) }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Button variant="outline" size="sm" class="flex-1" @click="openEditModal(user)">
                                <Pencil class="h-4 w-4 mr-2" /> Edit
                            </Button>
                            <Button variant="outline" size="sm"
                                class="flex-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 border-red-200 dark:border-red-900/30"
                                @click="openDeleteDialog(user)">
                                <Trash2 class="h-4 w-4 mr-2" /> Hapus
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State Mobile -->
                    <div v-if="users.length === 0"
                        class="text-center py-12 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <p class="text-zinc-500">Belum ada data karyawan</p>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Tambah Karyawan Baru</DialogTitle>
                        <DialogDescription>
                            Isi form berikut untuk menambah karyawan baru
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="create-name">Nama Lengkap</Label>
                            <Input id="create-name" v-model="createForm.name" type="text"
                                placeholder="Masukkan nama lengkap" required />
                            <InputError :message="createForm.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-email">Email</Label>
                            <Input id="create-email" v-model="createForm.email" type="email"
                                placeholder="email@example.com" required />
                            <InputError :message="createForm.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-password">Password</Label>
                            <Input id="create-password" v-model="createForm.password" type="password"
                                placeholder="Minimal 8 karakter" required />
                            <InputError :message="createForm.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-password-confirmation">Konfirmasi Password</Label>
                            <Input id="create-password-confirmation" v-model="createForm.password_confirmation"
                                type="password" placeholder="Ulangi password" required />
                            <InputError :message="createForm.errors.password_confirmation" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-role">Role</Label>
                            <select id="create-role" v-model="createForm.role"
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600"
                                required>
                                <option value="admin">Admin</option>
                                <option value="cashier">Cashier</option>
                            </select>
                            <InputError :message="createForm.errors.role" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-branch">Cabang</Label>
                            <select id="create-branch" v-model="createForm.branch_id"
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600">
                                <option :value="null">-- Tidak ada cabang --</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                    {{ branch.nama }}
                                </option>
                            </select>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Pilih cabang untuk karyawan (opsional
                                untuk admin)</p>
                            <InputError :message="createForm.errors.branch_id" />
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
                        <DialogTitle>Edit Karyawan</DialogTitle>
                        <DialogDescription>
                            Perbarui data karyawan {{ userToEdit?.name }}
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-name">Nama Lengkap</Label>
                            <Input id="edit-name" v-model="editForm.name" type="text"
                                placeholder="Masukkan nama lengkap" required />
                            <InputError :message="editForm.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-email">Email</Label>
                            <Input id="edit-email" v-model="editForm.email" type="email" placeholder="email@example.com"
                                required />
                            <InputError :message="editForm.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-password">Password Baru</Label>
                            <Input id="edit-password" v-model="editForm.password" type="password"
                                placeholder="Kosongkan jika tidak ingin mengubah" />
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Kosongkan jika tidak ingin mengubah
                                password</p>
                            <InputError :message="editForm.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-password-confirmation">Konfirmasi Password Baru</Label>
                            <Input id="edit-password-confirmation" v-model="editForm.password_confirmation"
                                type="password" placeholder="Ulangi password baru" />
                            <InputError :message="editForm.errors.password_confirmation" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-role">Role</Label>
                            <select id="edit-role" v-model="editForm.role"
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600"
                                required>
                                <option value="admin">Admin</option>
                                <option value="cashier">Cashier</option>
                            </select>
                            <InputError :message="editForm.errors.role" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-branch">Cabang</Label>
                            <select id="edit-branch" v-model="editForm.branch_id"
                                class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-md bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600">
                                <option :value="null">-- Tidak ada cabang --</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                    {{ branch.nama }}
                                </option>
                            </select>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Pilih cabang untuk karyawan (opsional
                                untuk admin)</p>
                            <InputError :message="editForm.errors.branch_id" />
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
                        <DialogTitle>Hapus Karyawan</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus karyawan <strong>{{ userToDelete?.name }}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="closeDeleteDialog">Batal</Button>
                        <Button variant="destructive" @click="deleteUser">Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </main>
    </AppLayout>
</template>
