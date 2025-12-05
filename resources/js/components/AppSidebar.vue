<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarGroupContent,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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

// Main Navigation Items (Top Level)
const mainNavItems = computed(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            permission: null, // Everyone can access
        },
        {
            title: 'Kasir / POS',
            href: '/pos',
            icon: ShoppingCart,
            permission: 'manage_pos',
        },
        {
            title: 'Riwayat Transaksi',
            href: '/pos/history',
            icon: Clock,
            permission: 'manage_pos', // Both admin & cashier
        },
    ];

    return items.filter(item => {
        if (!item.permission) return true;
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });
});

// Manajemen Group (Collapsible - Admin Only)
const manajemenGroup = computed(() => {
    if (!permissions.value.manage_branches && !permissions.value.manage_menu && !permissions.value.manage_employees) {
        return null; // Don't show group if user has no permissions
    }

    const items: NavItem[] = [
        {
            title: 'Manajemen Cabang',
            href: '/branch',
            icon: Store,
            permission: 'manage_branches',
        },
        {
            title: 'Manajemen Menu',
            href: '/menu',
            icon: Utensils,
            permission: 'manage_menu',
        },
        {
            title: 'Karyawan',
            href: '/karyawan',
            icon: Users,
            permission: 'manage_employees',
        },
    ];

    const filtered = items.filter(item => {
        if (!item.permission) return true;
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });

    return filtered.length > 0 ? { title: 'Manajemen', items: filtered } : null;
});

// Laporan Group (Collapsible - Admin Only)
const laporanGroup = computed(() => {
    if (!permissions.value.view_reports) {
        return null;
    }

    const items: NavItem[] = [
        {
            title: 'Laporan Keuangan',
            href: '/reports/transactions',
            icon: FileText,
            permission: 'view_reports',
        },
        {
            title: 'Analisa Menu',
            href: '/reports/menu-analysis',
            icon: PieChart,
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

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                        <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- Main Navigation Items -->
            <SidebarGroup>
                <SidebarGroupLabel>Navigasi Utama</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                            <SidebarMenuButton as-child :tooltip="item.title">
                                <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Manajemen Group (Collapsible) -->
            <Collapsible v-if="manajemenGroup" default-open class="group/collapsible">
                <SidebarGroup>
                    <SidebarGroupLabel as-child>
                        <CollapsibleTrigger class="w-full">
                            {{ manajemenGroup.title }}
                            <ChevronRight
                                class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                        </CollapsibleTrigger>
                    </SidebarGroupLabel>
                    <CollapsibleContent>
                        <SidebarGroupContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="item in manajemenGroup.items" :key="item.title">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="item.href">
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ item.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </SidebarGroupContent>
                    </CollapsibleContent>
                </SidebarGroup>
            </Collapsible>

            <!-- Laporan Group (Collapsible) -->
            <Collapsible v-if="laporanGroup" default-open class="group/collapsible">
                <SidebarGroup>
                    <SidebarGroupLabel as-child>
                        <CollapsibleTrigger class="w-full">
                            {{ laporanGroup.title }}
                            <ChevronRight
                                class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                        </CollapsibleTrigger>
                    </SidebarGroupLabel>
                    <CollapsibleContent>
                        <SidebarGroupContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="item in laporanGroup.items" :key="item.title">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="item.href">
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ item.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </SidebarGroupContent>
                    </CollapsibleContent>
                </SidebarGroup>
            </Collapsible>

            <!-- Gudang Section (Admin Only - Coming Soon Features) -->
            <SidebarGroup v-if="gudangItems.length > 0">
                <SidebarGroupLabel>Gudang</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in gudangItems" :key="item.title">
                            <SidebarMenuButton @click="handleComingSoon(item.title)" class="cursor-pointer">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <span
                                    class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400">
                                    Soon
                                </span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
