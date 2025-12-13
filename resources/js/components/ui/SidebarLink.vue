<script setup lang="ts">
import { Link, type InertiaLinkProps } from '@inertiajs/vue3';
import type { Component } from 'vue';

const props = defineProps<{
    href: NonNullable<InertiaLinkProps['href']>;
    active?: boolean;
    icon: Component;
    label: string;
    isExpanded: boolean;
}>();
</script>

<template>
    <Link :href="props.href" :class="[
        'relative w-full flex items-center transition-all duration-200 ease-out group overflow-hidden rounded-2xl',
        'transform-gpu will-change-transform',
        props.isExpanded ? 'px-6 py-4 justify-start gap-4' : 'px-0 py-4 justify-center',
        props.active
            ? 'text-orange-600 dark:text-orange-500 font-bold'
            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-slate-200'
    ]">
        <!-- Layer 1: Active Background (The Glow) - z-0 -->
        <div v-if="props.active" class="absolute inset-0 bg-orange-50 dark:bg-orange-500/10 z-0"></div>

        <!-- Layer 2: Active Indicator (The Bar) - z-10 -->
        <div v-if="props.active"
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-10 bg-orange-500 rounded-r-full z-10"></div>

        <!-- Layer 3: Content (Icon & Text) - z-20 -->
        <!-- Icon -->
        <component :is="props.icon"
            class="w-[22px] h-[22px] stroke-[2] transition-transform duration-300 group-hover:scale-110 shrink-0 relative z-20" />

        <!-- Text -->
        <span :class="[
            'font-medium text-sm whitespace-nowrap transition-all duration-300 relative z-20',
            props.isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0 overflow-hidden'
        ]">
            {{ props.label }}
        </span>
    </Link>
</template>
