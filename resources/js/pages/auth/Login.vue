<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/auth/AuthEpokLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase title="Selamat Datang Kembali" description="Silakan masuk ke akun Anda untuk mengelola POS">

        <Head title="Masuk" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }"
            class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="text-slate-700 font-semibold ml-1">Email</Label>
                    <div class="relative group">
                        <Input id="email" type="email" name="email" required autofocus :tabindex="1"
                            autocomplete="email" placeholder="nama@email.com"
                            class="rounded-2xl border-slate-200 focus:ring-orange-500/20 focus:border-orange-500 transition-all h-12 px-4 text-slate-900 bg-white" />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between ml-1">
                        <Label for="password" class="text-slate-700 font-semibold">Password</Label>
                        <TextLink v-if="canResetPassword" :href="request()"
                            class="text-xs font-bold text-orange-600 hover:text-orange-700" :tabindex="5">
                            Lupa password?
                        </TextLink>
                    </div>
                    <div class="relative group">
                        <Input id="password" type="password" name="password" required :tabindex="2"
                            autocomplete="current-password" placeholder="••••••••"
                            class="rounded-2xl border-slate-200 focus:ring-orange-500/20 focus:border-orange-500 transition-all h-12 px-4 text-slate-900 bg-white" />
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between ml-1">
                    <Label for="remember" class="flex items-center space-x-3 cursor-pointer group">
                        <Checkbox id="remember" name="remember" :tabindex="3"
                            class="rounded-md border-slate-300 text-orange-600 focus:ring-orange-500" />
                        <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors">Ingat
                            saya</span>
                    </Label>
                </div>

                <Button type="submit"
                    class="mt-4 w-full h-12 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-400 hover:from-orange-600 hover:to-amber-500 text-white font-bold shadow-xl shadow-orange-500/30 transition-all active:scale-[0.98] border-none"
                    :tabindex="4" :disabled="processing" data-test="login-button">
                    <Spinner v-if="processing" />
                    Masuk Sekarang
                </Button>
            </div>

            <div class="text-center text-sm text-slate-500" v-if="canRegister">
                Belum punya akun?
                <TextLink :href="register()" :tabindex="5" class="font-bold text-orange-600 hover:text-orange-700 ml-1">
                    Daftar</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
