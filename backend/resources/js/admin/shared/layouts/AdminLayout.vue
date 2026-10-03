<template>
    <div class="min-h-screen bg-white text-slate-900">
        <AdminErrorNotice />
        <header class="border-b">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <span class="font-semibold">ForTechLead Admin</span>
                <nav class="flex items-center gap-5 text-sm text-slate-600">
                    <Link href="/admin">Início</Link>
                    <Link href="/admin/tenants">Tenants</Link>
                    <Link href="/admin/access-logs">Acessos</Link>
                    <Link href="/admin/mcp/prompts">Prompts MCP</Link>
                </nav>
                <div class="flex items-center gap-4 text-sm">
                    <span>{{ adminName }}</span>
                    <Link href="/admin/logout" method="post" as="button" class="text-slate-600 hover:text-slate-900">
                        Sair
                    </Link>
                </div>
            </div>
        </header>
        <div v-if="successMessage" class="mx-auto mt-5 max-w-7xl px-6">
            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ successMessage }}
            </p>
        </div>
        <slot />
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminErrorNotice from '../components/AdminErrorNotice.vue';

const page = usePage<{
    auth: { admin: { name: string } | null };
    flash: { success?: string | null };
}>();

const adminName = computed(() => page.props.auth.admin?.name ?? 'Administrador');
const successMessage = computed(() => page.props.flash.success ?? null);
</script>
