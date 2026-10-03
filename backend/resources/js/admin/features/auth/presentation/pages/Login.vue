<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Acesso administrativo" />

    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-12">
        <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-8">
                <p class="text-sm font-medium text-slate-500">ForTechLead</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                    Acesso administrativo
                </h1>
            </div>

            <Form action="/admin/login" method="post" #default="{ errors, processing }">
                <div class="space-y-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium" for="email">E-mail</label>
                        <input
                            id="email"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            type="email"
                            name="email"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        <p v-if="errors.email" class="mt-1.5 text-sm text-red-700">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium" for="password">Senha</label>
                        <input
                            id="password"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                        <p v-if="errors.password" class="mt-1.5 text-sm text-red-700">{{ errors.password }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                        Manter sessão
                    </label>

                    <button
                        class="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60"
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Entrando…' : 'Entrar' }}
                    </button>
                </div>
            </Form>
        </section>
    </main>
</template>
