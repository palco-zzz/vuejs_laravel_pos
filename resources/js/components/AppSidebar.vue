<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, BookOpen, Folder, LayoutGrid, Package, Package2, Package2Icon, ShoppingBag, Store, User } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const permissions = computed(() => page.props.auth.permissions || {});

// All available navigation items
const allNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        permission: null, // Everyone can access
    },
    {
        title: 'Penjualan (POS)',
        href: '/pos',
        icon: ShoppingBag,
        permission: 'manage_pos',
    },
    {
        title: 'Manajemen Cabang',
        href: '/branch',
        icon: Store,
        permission: 'manage_branches',
    },
    {
        title: 'Manajemen Menu',
        href: '/menu',
        icon: Package,
        permission: 'manage_menu',
    },
    {
        title: 'Stok Bahan',
        href: '/stok',
        icon: Package,
        permission: 'manage_menu', // Same as menu management
    },
    {
        title: 'Karyawan',
        href: '/karyawan',
        icon: User,
        permission: 'manage_employees',
    },
    {
        title: 'Laporan',
        href: '/reports/transactions',
        icon: BarChart3,
        permission: 'view_reports',
    },
];

// Filter nav items based on permissions
const mainNavItems = computed(() => {
    return allNavItems.filter(item => {
        // If no permission required, show to everyone
        if (!item.permission) return true;

        // Check if user has the required permission
        return permissions.value[item.permission as keyof typeof permissions.value] === true;
    });
});

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
            <NavMain :items="mainNavItems" class="space-y-1" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
