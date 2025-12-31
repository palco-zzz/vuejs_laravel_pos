<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/auth/AuthEpokLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
import { Select } from 'reka-ui/namespaced';
</script>

<template>
    <AuthBase title="Daftar Akun Baru" description="Lengkapi detail di bawah untuk membuat akun Anda">

        <Head title="Daftar" />

        <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name" class="text-slate-700 font-semibold ml-1">Nama Lengkap</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name"
                        placeholder="Nama lengkap Anda"
                        class="rounded-2xl border-slate-200 focus:ring-orange-500/20 focus:border-orange-500 transition-all h-12 px-4 text-slate-900 bg-white" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-slate-700 font-semibold ml-1">Email</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email"
                        placeholder="nama@email.com"
                        class="rounded-2xl border-slate-200 focus:ring-orange-500/20 focus:border-orange-500 transition-all h-12 px-4 text-slate-900 bg-white" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-slate-700 font-semibold ml-1">Password</Label>
                    <Input id="password" type="password" required :tabindex="3" autocomplete="new-password"
                        name="password" placeholder="••••••••"
                        class="rounded-2xl border-slate-200 focus:ring-orange-500/20 focus:border-orange-500 transition-all h-12 px-4 text-slate-900 bg-white" />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-slate-700 font-semibold ml-1">Konfirmasi
                        Password</Label>
                    <Input id="password_confirmation" type="password" required :tabindex="4" autocomplete="new-password"
                        name="password_confirmation" placeholder="••••••••"
                        class="rounded-2xl border-slate-200 focus:ring-orange-500/20 focus:border-orange-500 transition-all h-12 px-4 text-slate-900 bg-white" />
                    <InputError :message="errors.password_confirmation" />
                </div>
                <div class="grid gap-2">
                    <Label for="role" class="text-slate-700 font-semibold ml-1">Pilih Peran</Label>
                    <div class="relative">
                        <select id="role" name="role"
                            class="flex h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 py-1 text-sm text-slate-900 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 cursor-pointer appearance-none"
                            required>
                            <option value="admin" class="text-slate-900">Admin</option>
                            <option value="cashier" selected class="text-slate-900">Kasir</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </div>
                    <InputError :message="errors.role" />
                </div>

                <Button type="submit"
                    class="mt-2 w-full h-12 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-400 hover:from-orange-600 hover:to-amber-500 text-white font-bold shadow-xl shadow-orange-500/30 transition-all active:scale-[0.98] border-none"
                    tabindex="5" :disabled="processing" data-test="register-user-button">
                    <Spinner v-if="processing" />
                    Daftar Sekarang
                </Button>
            </div>

            <div class="text-center text-sm text-slate-500">
                Sudah punya akun?
                <TextLink :href="login()" class="font-bold text-orange-600 hover:text-orange-700 ml-1" :tabindex="6">
                    Masuk</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
