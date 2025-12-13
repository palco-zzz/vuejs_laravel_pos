<script setup lang="ts">
/**
 * GlassCard Component
 * 
 * A premium glassmorphism card with adaptive light/dark mode styling.
 * Based on the design reference with enhanced dark mode support.
 */

interface Props {
  /** Remove default padding for custom content layouts */
  noPadding?: boolean;
}

withDefaults(defineProps<Props>(), {
  noPadding: false,
});
</script>

<template>
  <div :class="[
    // Base glassmorphism styles + GPU acceleration
    'relative overflow-hidden rounded-[2rem]',
    'transform-gpu will-change-transform',

    // Conditional blur: solid on mobile, blur on desktop for performance
    'bg-white/95 md:bg-white/60',
    'md:backdrop-blur-xl',

    // Snappier transitions
    'transition-all duration-200 ease-out',

    // Light mode styles (reference design)
    'border border-white/40',
    'shadow-[0_8px_30px_rgba(0,0,0,0.04)]',
    'hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)]',

    // Dark mode styles
    'dark:bg-slate-900/95 md:dark:bg-slate-900/70',
    'dark:border-slate-700/50',
    'dark:shadow-[0_8px_30px_rgba(0,0,0,0.2)]',
    'dark:hover:shadow-[0_8px_30px_rgba(0,0,0,0.3)]',

    // Conditional padding
    noPadding ? '' : 'p-6',
  ]">
    <!-- Inner subtle gradient overlay -->
    <div class="
        absolute inset-0 pointer-events-none
        bg-gradient-to-br from-white/30 to-transparent
        dark:from-slate-800/20 dark:to-slate-900/10
      " aria-hidden="true" />

    <!-- Content slot with relative positioning to stack above gradient -->
    <div class="relative z-10 text-slate-900 dark:text-slate-100">
      <slot />
    </div>
  </div>
</template>
