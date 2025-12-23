<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    useSidebar,
} from '@/components/ui/sidebar';
import SidebarLink from '@/components/ui/SidebarLink.vue';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    ChevronRight,
    Clock,
    FileText,
    Folder,
    LayoutGrid,
    Package,
    PieChart,
    ShoppingCart,
    Store,
    Users,
    Utensils
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';
import { useToast } from '@/composables/useToast';

const page = usePage();
const permissions = computed(() => page.props.auth.permissions || {});
const { info } = useToast();

// Safely get sidebar context - may not be available during initial render
let sidebarState: { state: { value: 'expanded' | 'collapsed' } } | null = null;
try {
    sidebarState = useSidebar();
} catch (e) {
    // Context not available yet, will use fallback
    console.warn('Sidebar context not available, using fallback');
}

// Declare Ziggy's route helper for TypeScript
declare function route(name?: string, params?: any, absolute?: boolean): string & { current: (name?: string, params?: any) => boolean };

const isExpanded = computed(() => sidebarState?.state?.value === 'expanded' || false);

// Main Navigation Items (Top Level)
const mainNavItems = computed(() => {
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            active: currentPath === '/dashboard' || currentPath === '/',
            permission: null, // Everyone can access
        },
        {
            title: 'Kasir / POS',
            href: '/pos',
            icon: ShoppingCart,
            active: currentPath === '/pos',
            permission: 'manage_pos',
        },
        {
            title: 'Riwayat Transaksi',
            href: '/pos/history',
            icon: Clock,
            active: currentPath === '/pos/history',
            permission: 'manage_pos', // Both admin & cashier
        },
    ];

    return items.filter(item => {
        if (!item.permission) return true;
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });
});

// Manajemen Group (Admin Only)
const manajemenGroup = computed(() => {
    if (!permissions.value.manage_branches && !permissions.value.manage_menu && !permissions.value.manage_employees) {
        return null; // Don't show group if user has no permissions
    }

    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    const items: NavItem[] = [
        {
            title: 'Manajemen Cabang',
            href: '/branch',
            icon: Store,
            active: currentPath.startsWith('/branch'),
            permission: 'manage_branches',
        },
        {
            title: 'Manajemen Menu',
            href: '/menu',
            icon: Utensils,
            active: currentPath.startsWith('/menu'),
            permission: 'manage_menu',
        },
        {
            title: 'Karyawan',
            href: '/karyawan',
            icon: Users,
            active: currentPath.startsWith('/karyawan'),
            permission: 'manage_employees',
        },
    ];

    const filtered = items.filter(item => {
        if (!item.permission) return true;
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });

    return filtered.length > 0 ? { title: 'Manajemen', items: filtered } : null;
});

// Laporan Group (Admin Only)
const laporanGroup = computed(() => {
    if (!permissions.value.view_reports) {
        return null;
    }

    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    const items: NavItem[] = [
        {
            title: 'Laporan Keuangan',
            href: '/reports/transactions',
            icon: FileText,
            active: currentPath === '/reports/transactions',
            permission: 'view_reports',
        },
        {
            title: 'Analisa Menu',
            href: '/reports/menu-analysis',
            icon: PieChart,
            active: currentPath === '/reports/menu-analysis',
            permission: 'view_reports',
        },
    ];

    const filtered = items.filter(item => {
        if (!item.permission) return true;
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });

    return filtered.length > 0 ? { title: 'Laporan', items: filtered } : null;
});

// Gudang Section (Admin Only)
const gudangItems = computed(() => {
    if (!permissions.value.manage_menu) return [];

    return [
        {
            title: 'Stok Bahan',
            icon: Package,
            isComingSoon: true, // Special flag
        }
    ];
});

// Handle "Coming Soon" features
const handleComingSoon = (featureName: string) => {
    info(
        'Fitur Belum Tersedia',
        `Modul ${featureName} akan segera hadir di update berikutnya.`
    );
};

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
        <!-- Logo Header -->
        <SidebarHeader>
            <SidebarMenu>
                <div class="flex items-center gap-2 p-2 transition-all duration-200 ease-out transform-gpu will-change-[width]"
                    :class="isExpanded ? 'px-4' : 'justify-center px-2'">
                    <!-- Logo centered if collapsed -->
                    <div class="relative flex items-center justify-center">
                        <div class="bg-orange-600 text-white font-bold rounded-xl flex items-center justify-center shadow-lg transform-gpu transition-all duration-200 ease-out"
                            :class="isExpanded ? 'h-10 w-10 text-xl' : 'h-8 w-8 text-lg'">
                            E
                        </div>
                    </div>

                    <!-- Text hidden if collapsed -->
                    <div class="flex flex-col overflow-hidden transition-all duration-200 ease-out transform-gpu"
                        :class="isExpanded ? 'w-auto opacity-100 ml-3' : 'w-0 opacity-0 ml-0'">
                        <span class="font-bold text-lg text-slate-900 dark:text-white leading-none">Epok POS</span>
                        <span class="text-[10px] text-slate-500 font-medium tracking-wider uppercase mt-0.5">Management
                            System</span>
                    </div>
                </div>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-3 py-2 space-y-6 scrollbar-hide">
            <!-- Main Navigation Items -->
            <div class="space-y-1">
                <div v-if="isExpanded"
                    class="px-3 mb-2 text-xs font-bold text-slate-400 uppercase tracking-wider transition-opacity duration-300">
                    Navigasi Utama
                </div>
                <!-- Collapsed Helper Label -->
                <div v-else class="h-6 mb-2 flex items-center justify-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                </div>

                <SidebarLink v-for="item in mainNavItems" :key="item.title" :href="item.href || '#'"
                    :active="item.active" :icon="item.icon || Folder" :label="item.title" :isExpanded="isExpanded" />
            </div>

            <!-- Manajemen Group -->
            <div v-if="manajemenGroup" class="space-y-1">
                <div v-if="isExpanded"
                    class="px-3 mb-2 text-xs font-bold text-slate-400 uppercase tracking-wider transition-opacity duration-300">
                    {{ manajemenGroup.title }}
                </div>
                <div v-else class="h-6 mb-2 flex items-center justify-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                </div>

                <SidebarLink v-for="item in manajemenGroup.items" :key="item.title" :href="item.href || '#'"
                    :active="item.active" :icon="item.icon || Folder" :label="item.title" :isExpanded="isExpanded" />
            </div>

            <!-- Laporan Group -->
            <div v-if="laporanGroup" class="space-y-1">
                <div v-if="isExpanded"
                    class="px-3 mb-2 text-xs font-bold text-slate-400 uppercase tracking-wider transition-opacity duration-300">
                    {{ laporanGroup.title }}
                </div>
                <div v-else class="h-6 mb-2 flex items-center justify-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                </div>

                <SidebarLink v-for="item in laporanGroup.items" :key="item.title" :href="item.href || '#'"
                    :active="item.active" :icon="item.icon || Folder" :label="item.title" :isExpanded="isExpanded" />
            </div>

            <!-- Gudang Section (Coming Soon) -->
            <div v-if="gudangItems.length > 0" class="space-y-1">
                <div v-if="isExpanded"
                    class="px-3 mb-2 text-xs font-bold text-slate-400 uppercase tracking-wider transition-opacity duration-300">
                    Gudang
                </div>
                <div v-else class="h-6 mb-2 flex items-center justify-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                </div>

                <div v-for="item in gudangItems" :key="item.title" @click="handleComingSoon(item.title)"
                    class="cursor-pointer">
                    <!-- Usage of SidebarLink style manually for non-link item -->
                    <div :class="[
                        'flex items-center gap-4 p-3 rounded-2xl transition-all duration-200 ease-out group relative transform-gpu',
                        'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-slate-200',
                        !isExpanded ? 'justify-center' : ''
                    ]">
                        <component :is="item.icon || Package"
                            class="w-[22px] h-[22px] transition-transform duration-300 group-hover:scale-110 shrink-0" />

                        <span :class="[
                            'whitespace-nowrap overflow-hidden transition-all duration-300 flex items-center gap-2',
                            isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'
                        ]">
                            {{ item.title }}
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 font-bold">
                                Soon
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
