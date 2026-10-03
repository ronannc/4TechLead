<script setup lang="ts">
import { Form, Link, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../../shared/layouts/AdminLayout.vue';

interface Tenant {
    id: number;
    name: string;
    status: 'active' | 'blocked';
    blocked_at: string | null;
    blocked_reason: string | null;
    blocked_by_admin_user: { id: number; name: string } | null;
    users_count: number;
    created_at: string;
}

const props = defineProps<{
    tenant: Tenant;
    recentAccesses: Array<{ id: number; user: string | null; email: string | null; ip_address: string | null; created_at: string | null }>;
}>();

const showBlockForm = ref(false);

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <Head :title="props.tenant.name" />
    <AdminLayout>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <Link class="text-sm text-slate-600 hover:text-slate-900" href="/admin/tenants">← Todos os tenants</Link>
            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ tenant.name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ tenant.users_count }} usuários · Criado em {{ formatDate(tenant.created_at) }}</p>
                </div>
                <button
                    v-if="tenant.status === 'active'"
                    class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800"
                    type="button"
                    @click="showBlockForm = !showBlockForm"
                >
                    Bloquear acesso
                </button>
                <Form v-else :action="`/admin/tenants/${tenant.id}/unblock`" method="post" #default="{ processing }">
                    <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60" type="submit" :disabled="processing">
                        {{ processing ? 'Desbloqueando…' : 'Desbloquear acesso' }}
                    </button>
                </Form>
            </div>

            <section v-if="showBlockForm && tenant.status === 'active'" class="mt-6 max-w-2xl rounded-lg border border-red-200 bg-red-50 p-5">
                <h2 class="font-semibold">Confirmar bloqueio</h2>
                <p class="mt-1 text-sm text-slate-600">Os usuários deste tenant deixarão de acessar o produto. Os dados serão preservados.</p>
                <Form class="mt-4" :action="`/admin/tenants/${tenant.id}/block`" method="post" #default="{ errors, processing }" @success="showBlockForm = false">
                    <label class="mb-1.5 block text-sm font-medium" for="reason">Motivo do bloqueio</label>
                    <textarea id="reason" class="min-h-24 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" name="reason" minlength="5" maxlength="1000" required />
                    <p v-if="errors.reason" class="mt-1.5 text-sm text-red-700">{{ errors.reason }}</p>
                    <div class="mt-3 flex gap-2">
                        <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-60" type="submit" :disabled="processing">
                            {{ processing ? 'Bloqueando…' : 'Confirmar bloqueio' }}
                        </button>
                        <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm" type="button" @click="showBlockForm = false">Cancelar</button>
                    </div>
                </Form>
            </section>

            <section v-if="tenant.status === 'blocked'" class="mt-6 rounded-lg border border-red-200 bg-red-50 p-5">
                <h2 class="font-semibold text-red-900">Tenant bloqueado</h2>
                <p class="mt-1 text-sm text-red-900">Motivo: {{ tenant.blocked_reason }}</p>
                <p class="mt-1 text-xs text-red-800">Por {{ tenant.blocked_by_admin_user?.name ?? 'administrador removido' }} em {{ formatDate(tenant.blocked_at) }}</p>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">Últimos acessos</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3 font-medium">Usuário</th><th class="px-4 py-3 font-medium">IP</th><th class="px-4 py-3 font-medium">Data e hora</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="access in recentAccesses" :key="access.id">
                                <td class="px-4 py-3"><span class="font-medium">{{ access.user ?? 'Usuário removido' }}</span><span v-if="access.email" class="ml-2 text-slate-500">{{ access.email }}</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ access.ip_address ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ formatDate(access.created_at) }}</td>
                            </tr>
                            <tr v-if="recentAccesses.length === 0"><td class="px-4 py-8 text-center text-slate-500" colspan="3">Ainda não há acessos registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AdminLayout>
</template>
