<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import MobileNavBar from '@/components/ui/MobileNavBar.vue';
import MobileMenuDrawer from '@/components/ui/MobileMenuDrawer.vue';
import type { BreadcrumbItemType } from '@/types';
import { ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Mobile drawer state
const isMobileDrawerOpen = ref(false);
</script>

<template>
    <AppShell variant="sidebar">
        <!-- Desktop Sidebar - Hidden on mobile -->
        <AppSidebar class="hidden md:flex" />
        
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <!-- Main content with bottom padding for mobile nav -->
            <div class="pb-24 md:pb-0">
                <slot />
            </div>
        </AppContent>
    </AppShell>
    
    <!-- Toast Notifications -->
    <ToastNotification />
    
    <!-- Mobile Navigation - Hidden on desktop -->
    <MobileNavBar 
        class="md:hidden" 
        @open-drawer="isMobileDrawerOpen = true" 
    />
    <MobileMenuDrawer 
        :is-open="isMobileDrawerOpen" 
        @close="isMobileDrawerOpen = false" 
    />
</template>
