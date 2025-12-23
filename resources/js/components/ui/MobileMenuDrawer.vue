<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    X,
    Store,
    Utensils,
    Users,
    LogOut,
    Settings,
    ChevronRight
} from 'lucide-vue-next';
import { computed, watch } from 'vue';
import type { Component } from 'vue';

interface MenuItem {
    id: string;
    label: string;
    href?: string;
    icon: Component;
    permission?: string;
    isDanger?: boolean;
    isExternal?: boolean;
}

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const page = usePage();
const permissions = computed(() => page.props.auth.permissions || {});

// Get current path for active state
const currentPath = computed(() => typeof window !== 'undefined' ? window.location.pathname : '');

// Management menu items
const menuItems = computed<MenuItem[]>(() => {
    const items: MenuItem[] = [
        {
            id: 'branch',
            label: 'Manajemen Cabang',
            href: '/branch',
            icon: Store,
            permission: 'manage_branches',
        },
        {
            id: 'menu',
            label: 'Manajemen Menu',
            href: '/menu',
            icon: Utensils,
            permission: 'manage_menu',
        },
        {
            id: 'employee',
            label: 'Karyawan',
            href: '/karyawan',
            icon: Users,
            permission: 'manage_employees',
        },
    ];

    // Filter by permissions
    return items.filter(item => {
        if (!item.permission) return true;
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });
});

// Check if item is active
const isActive = (href: string) => currentPath.value.startsWith(href);

// Prevent body scroll when drawer is open
watch(() => props.isOpen, (isOpen) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
});

const handleClose = () => {
    emit('close');
};

const handleOverlayClick = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <!-- Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[60]"
                @click="handleOverlayClick"
            />
        </Transition>

        <!-- Drawer Panel -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="isOpen"
                class="fixed bottom-0 left-0 right-0 w-full bg-white dark:bg-slate-900 rounded-t-[2rem] z-[70] max-h-[85vh] overflow-hidden shadow-2xl"
            >
                <!-- Drag Handle -->
                <div class="flex justify-center pt-3 pb-2">
                    <div class="w-12 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full" />
                </div>

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        Menu Lainnya
                    </h2>
                    <button
                        @click="handleClose"
                        class="p-2 -mr-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Menu Content -->
                <div class="overflow-y-auto max-h-[calc(85vh-120px)] overscroll-contain">
                    <!-- Management Section -->
                    <div v-if="menuItems.length > 0" class="p-4">
                        <p class="px-2 mb-2 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Manajemen
                        </p>
                        <div class="space-y-1">
                            <Link
                                v-for="item in menuItems"
                                :key="item.id"
                                :href="item.href!"
                                class="flex items-center gap-4 p-4 rounded-2xl transition-all duration-200 group active:scale-[0.98]"
                                :class="isActive(item.href!) 
                                    ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-500' 
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                                @click="handleClose"
                            >
                                <div 
                                    class="p-2.5 rounded-xl transition-colors"
                                    :class="isActive(item.href!) 
                                        ? 'bg-orange-100 dark:bg-orange-500/20' 
                                        : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700'"
                                >
                                    <component 
                                        :is="item.icon" 
                                        class="w-5 h-5"
                                        :class="isActive(item.href!) 
                                            ? 'text-orange-600 dark:text-orange-500' 
                                            : 'text-slate-500 dark:text-slate-400'"
                                    />
                                </div>
                                <span class="flex-1 font-medium">{{ item.label }}</span>
                                <ChevronRight 
                                    class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-slate-400 dark:group-hover:text-slate-500 transition-colors" 
                                />
                            </Link>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="mx-6 border-t border-slate-100 dark:border-slate-800" />

                    <!-- Logout Section -->
                    <div class="p-4 pb-8">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="flex items-center gap-4 w-full p-4 rounded-2xl text-red-600 dark:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all duration-200 active:scale-[0.98]"
                            @click="handleClose"
                        >
                            <div class="p-2.5 rounded-xl bg-red-50 dark:bg-red-500/10">
                                <LogOut class="w-5 h-5" />
                            </div>
                            <span class="flex-1 font-medium text-left">Keluar</span>
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
