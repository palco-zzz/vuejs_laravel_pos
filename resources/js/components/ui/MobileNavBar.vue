<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Clock,
    ShoppingBag,
    FileText,
    Menu
} from 'lucide-vue-next';
import { computed } from 'vue';

// Declare Ziggy's route helper for TypeScript
declare function route(name?: string, params?: any, absolute?: boolean): string & { current: (name?: string, params?: any) => boolean };

const emit = defineEmits<{
    (e: 'open-drawer'): void;
}>();

const page = usePage();
const permissions = computed(() => page.props.auth.permissions || {});

// Get current path for active state
const currentPath = computed(() => typeof window !== 'undefined' ? window.location.pathname : '');

// Check if user can view reports
const canViewReports = computed(() => permissions.value.view_reports === true);

// Navigation items configuration
const navItems = computed(() => [
    {
        id: 'dashboard',
        label: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        isActive: currentPath.value === '/dashboard' || currentPath.value === '/',
    },
    {
        id: 'history',
        label: 'Riwayat',
        href: '/pos/history',
        icon: Clock,
        isActive: currentPath.value === '/pos/history',
    },
    {
        id: 'pos',
        label: 'POS',
        href: '/pos',
        icon: ShoppingBag,
        isActive: currentPath.value === '/pos',
        isHero: true, // Special floating button
    },
    {
        id: 'report',
        label: 'Laporan',
        href: '/reports/transactions',
        icon: FileText,
        isActive: currentPath.value.startsWith('/reports'),
        visible: canViewReports.value,
    },
    {
        id: 'menu',
        label: 'Menu',
        icon: Menu,
        isActive: false,
        isDrawerTrigger: true,
    },
]);

// Filter visible items
const visibleNavItems = computed(() => 
    navItems.value.filter(item => item.visible !== false)
);

const handleMenuClick = () => {
    emit('open-drawer');
};
</script>

<template>
    <!-- Mobile Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 w-full z-50 md:hidden">
        <!-- Glassmorphism Container -->
        <div class="bg-white/80 dark:bg-slate-900/90 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 shadow-lg shadow-black/5">
            <!-- Safe area padding for notched devices -->
            <div class="px-2 pb-safe">
                <!-- Navigation Grid -->
                <div class="grid grid-cols-5 items-end relative h-16">
                    <template v-for="item in visibleNavItems" :key="item.id">
                        <!-- Hero POS Button (Center) -->
                        <div v-if="item.isHero" class="flex items-center justify-center relative">
                            <Link
                                :href="item.href!"
                                class="absolute -top-6 left-1/2 -translate-x-1/2 w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full shadow-lg shadow-orange-500/40 flex items-center justify-center text-white transform transition-all duration-300 active:scale-95 hover:shadow-xl hover:shadow-orange-500/50"
                                :class="{ 'ring-2 ring-orange-400 ring-offset-2 ring-offset-white dark:ring-offset-slate-900': item.isActive }"
                            >
                                <component :is="item.icon" class="w-6 h-6" />
                            </Link>
                            <!-- Placeholder to maintain grid spacing -->
                            <span class="text-[10px] font-medium text-slate-400 mt-6">{{ item.label }}</span>
                        </div>

                        <!-- Drawer Trigger (Menu) -->
                        <button
                            v-else-if="item.isDrawerTrigger"
                            @click="handleMenuClick"
                            class="flex flex-col items-center justify-center py-2 transition-colors duration-200 group"
                        >
                            <div class="p-1.5 rounded-xl transition-all duration-200 group-active:scale-95 group-active:bg-slate-100 dark:group-active:bg-slate-800">
                                <component 
                                    :is="item.icon" 
                                    class="w-5 h-5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors"
                                />
                            </div>
                            <span class="text-[10px] font-medium text-slate-400 mt-0.5">{{ item.label }}</span>
                        </button>

                        <!-- Regular Navigation Items -->
                        <Link
                            v-else
                            :href="item.href!"
                            class="flex flex-col items-center justify-center py-2 transition-colors duration-200 group"
                        >
                            <div 
                                class="p-1.5 rounded-xl transition-all duration-200 group-active:scale-95"
                                :class="item.isActive 
                                    ? 'bg-orange-50 dark:bg-orange-500/10' 
                                    : 'group-active:bg-slate-100 dark:group-active:bg-slate-800'"
                            >
                                <component 
                                    :is="item.icon" 
                                    class="w-5 h-5 transition-colors"
                                    :class="item.isActive 
                                        ? 'text-orange-500' 
                                        : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300'"
                                />
                            </div>
                            <span 
                                class="text-[10px] font-medium mt-0.5 transition-colors"
                                :class="item.isActive ? 'text-orange-500' : 'text-slate-400'"
                            >
                                {{ item.label }}
                            </span>
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
/* Safe area padding for notched devices (iPhone X+) */
.pb-safe {
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
}
</style>
