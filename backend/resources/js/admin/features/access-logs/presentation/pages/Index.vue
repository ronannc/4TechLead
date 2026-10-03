<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../../shared/layouts/AdminLayout.vue';

interface AccessLogRow {
    id: number;
    event: string;
    actor_type: string;
    actor_name: string | null;
    actor_email: string | null;
    tenant: { id: number; name: string } | null;
    ip_address: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    accessLogs: { data: AccessLogRow[]; links: PageLink[]; total: number };
    filters: { event: string; tenant_id: string | number };
}>();

const event = ref(props.filters.event);
const tenantId = ref(String(props.filters.tenant_id || ''));

function applyFilters(): void {
    router.get('/admin/access-logs', { event: event.value, tenant_id: tenantId.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <AdminLayout>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Acessos e ações administrativas</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ accessLogs.total }} eventos registrados</p>
                </div>
                <form class="flex flex-wrap gap-2" @submit.prevent="applyFilters">
                    <input v-model="event" class="rounded-md border border-slate-300 px-3 py-2 text-sm" type="search" placeholder="Evento (ex.: user.login)">
                    <input v-model="tenantId" class="w-36 rounded-md border border-slate-300 px-3 py-2 text-sm" type="number" min="1" placeholder="ID do tenant">
                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white" type="submit">Filtrar</button>
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3 font-medium">Evento</th><th class="px-4 py-3 font-medium">Ator</th><th class="px-4 py-3 font-medium">Tenant</th><th class="px-4 py-3 font-medium">IP</th><th class="px-4 py-3 font-medium">Data e hora</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="access in accessLogs.data" :key="access.id">
                            <td class="px-4 py-3"><span class="font-medium">{{ access.event }}</span><p v-if="access.metadata?.reason" class="mt-1 max-w-sm text-xs text-slate-500">{{ access.metadata.reason }}</p></td>
                            <td class="px-4 py-3"><span>{{ access.actor_name ?? 'Ator removido' }}</span><p class="text-xs text-slate-500">{{ access.actor_email ?? access.actor_type }}</p></td>
                            <td class="px-4 py-3">
                                <Link v-if="access.tenant" :href="`/admin/tenants/${access.tenant.id}`" class="hover:underline">{{ access.tenant.name }}</Link>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ access.ip_address ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(access.created_at) }}</td>
                        </tr>
                        <tr v-if="accessLogs.data.length === 0"><td class="px-4 py-10 text-center text-slate-500" colspan="5">Nenhum evento encontrado.</td></tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="accessLogs.links.length > 3" class="mt-5 flex flex-wrap gap-2" aria-label="Paginação">
                <template v-for="(link, index) in accessLogs.links" :key="index">
                    <Link v-if="link.url" :href="link.url" class="rounded border px-3 py-1.5 text-sm" :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-700'" v-html="link.label" />
                    <span v-else class="rounded border border-slate-200 px-3 py-1.5 text-sm text-slate-400" v-html="link.label" />
                </template>
            </nav>
        </main>
    </AdminLayout>
</template>
