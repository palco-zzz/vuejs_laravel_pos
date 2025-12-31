<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-[#F6F8FA] p-6 md:p-10 font-sans selection:bg-orange-500/20 selection:text-orange-600 relative overflow-hidden">
        <!-- Decorative Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
            <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] bg-orange-200/30 blur-[120px] rounded-full"></div>
            <div class="absolute -bottom-[10%] -left-[10%] w-[40%] h-[40%] bg-amber-200/20 blur-[120px] rounded-full"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-6">
                    <Link :href="home()" class="flex flex-col items-center gap-4 group">
                        <div class="w-16 h-16 bg-gradient-to-tr from-orange-500 to-amber-400 rounded-[1.5rem] flex items-center justify-center text-white shadow-xl shadow-orange-500/30 group-hover:scale-105 transition-transform duration-300">
                            <AppLogoIcon class="size-8 fill-current" />
                        </div>
                        <div class="text-center">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Roti Bakar Epok</h1>
                            <p class="text-[10px] text-slate-400 font-bold tracking-widest uppercase">Premium POS System</p>
                        </div>
                    </Link>
                </div>

                <div class="glass-card rounded-[2.5rem] p-8 md:p-10 bg-white/60 backdrop-blur-2xl border border-white/40 shadow-[0_20px_40px_-10px_rgba(0,0,0,0.05)] animate-fade-in-up">
                    <div class="mb-8 text-center">
                        <h2 class="text-xl font-bold text-slate-800">{{ title }}</h2>
                        <p class="text-sm text-slate-500 mt-1">{{ description }}</p>
                    </div>
                    <slot />
                </div>
                
                <p class="text-center text-xs text-slate-400">
                    &copy; 2025 Roti Bakar Epok. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.glass-card {
    background-color: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.4);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); filter: blur(5px); }
    to { opacity: 1; transform: translateY(0); filter: blur(0); }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
