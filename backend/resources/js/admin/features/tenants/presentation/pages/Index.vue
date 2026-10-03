<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../../shared/layouts/AdminLayout.vue';

interface TenantRow {
    id: number;
    name: string;
    status: 'active' | 'blocked';
    users_count: number;
    last_access_at: string | null;
    created_at: string;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    tenants: { data: TenantRow[]; links: PageLink[]; total: number };
    filters: { search: string; status: string };
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

function applyFilters(): void {
    router.get('/admin/tenants', { search: search.value, status: status.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Nunca';
    }

    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <AdminLayout>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Tenants</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ tenants.total }} tenants cadastrados</p>
                </div>
                <form class="flex flex-wrap gap-2" @submit.prevent="applyFilters">
                    <input
                        v-model="search"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        type="search"
                        placeholder="Buscar por nome"
                        aria-label="Buscar tenants"
                    >
                    <select v-model="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm" aria-label="Filtrar por status">
                        <option value="">Todos os status</option>
                        <option value="active">Ativo</option>
                        <option value="blocked">Bloqueado</option>
                    </select>
                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white" type="submit">Filtrar</button>
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tenant</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Usuários</th>
                            <th class="px-4 py-3 font-medium">Último acesso</th>
                            <th class="px-4 py-3 font-medium">Cadastro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium">
                                <Link class="text-slate-900 underline-offset-4 hover:underline" :href="`/admin/tenants/${tenant.id}`">
                                    {{ tenant.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="tenant.status === 'blocked' ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800'" class="rounded-full px-2.5 py-1 text-xs font-medium">
                                    {{ tenant.status === 'blocked' ? 'Bloqueado' : 'Ativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ tenant.users_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(tenant.last_access_at) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(tenant.created_at) }}</td>
                        </tr>
                        <tr v-if="tenants.data.length === 0">
                            <td class="px-4 py-10 text-center text-slate-500" colspan="5">Nenhum tenant encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="tenants.links.length > 3" class="mt-5 flex flex-wrap gap-2" aria-label="Paginação">
                <template v-for="(link, index) in tenants.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border px-3 py-1.5 text-sm"
                        :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-700'"
                        v-html="link.label"
                    />
                    <span v-else class="rounded border border-slate-200 px-3 py-1.5 text-sm text-slate-400" v-html="link.label" />
                </template>
            </nav>
        </main>
    </AdminLayout>
</template>
