<script setup lang="ts">
import { useToast, type Toast } from '@/composables/useToast';
import { X, CheckCircle, AlertCircle, Info } from 'lucide-vue-next';

const { toasts, removeToast } = useToast();

const getIcon = (variant: Toast['variant']) => {
    switch (variant) {
        case 'success':
            return CheckCircle;
        case 'destructive':
            return AlertCircle;
        default:
            return Info;
    }
};

const getVariantClasses = (variant: Toast['variant']) => {
    switch (variant) {
        case 'success':
            return 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200';
        case 'destructive':
            return 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200';
        default:
            return 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200';
    }
};

const getIconClasses = (variant: Toast['variant']) => {
    switch (variant) {
        case 'success':
            return 'text-green-600 dark:text-green-400';
        case 'destructive':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-zinc-600 dark:text-zinc-400';
    }
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
            <TransitionGroup name="toast">
                <div v-for="toast in toasts" :key="toast.id" :class="[
                    'pointer-events-auto rounded-lg border p-4 shadow-lg transition-all duration-300',
                    getVariantClasses(toast.variant)
                ]">
                    <div class="flex items-start gap-3">
                        <component :is="getIcon(toast.variant)"
                            :class="['h-5 w-5 flex-shrink-0 mt-0.5', getIconClasses(toast.variant)]" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">{{ toast.title }}</p>
                            <p v-if="toast.description" class="text-sm opacity-80 mt-1">
                                {{ toast.description }}
                            </p>
                        </div>
                        <button @click="removeToast(toast.id)"
                            class="flex-shrink-0 rounded-md p-1 hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active {
    animation: toast-in 0.3s ease-out;
}

.toast-leave-active {
    animation: toast-out 0.2s ease-in forwards;
}

@keyframes toast-in {
    from {
        opacity: 0;
        transform: translateX(100%);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes toast-out {
    from {
        opacity: 1;
        transform: translateX(0);
    }

    to {
        opacity: 0;
        transform: translateX(100%);
    }
}
</style>
