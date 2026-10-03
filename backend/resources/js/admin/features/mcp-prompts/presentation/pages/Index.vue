<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdminLayout from '../../../../shared/layouts/AdminLayout.vue';

interface PromptRow {
    key: string;
    title: string;
    description: string;
    current_version: number | null;
    draft_version: number | null;
    content: string;
}

defineProps<{ prompts: PromptRow[] }>();
</script>

<template>
    <AdminLayout>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <h1 class="text-2xl font-semibold tracking-tight">Configurações do MCP</h1>
            <p class="mt-1 text-sm text-slate-600">Edite as instruções e prompts gerais. Alterações só entram em vigor depois da publicação.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <Link
                    v-for="prompt in prompts"
                    :key="prompt.key"
                    :href="`/admin/mcp/prompts/${prompt.key}`"
                    class="rounded-lg border border-slate-200 p-5 transition hover:border-slate-400 hover:shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold">{{ prompt.title }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ prompt.description }}</p>
                        </div>
                        <span v-if="prompt.draft_version" class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">Rascunho v{{ prompt.draft_version }}</span>
                        <span v-else-if="prompt.current_version" class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">Publicado v{{ prompt.current_version }}</span>
                        <span v-else class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Padrão do sistema</span>
                    </div>
                    <p class="mt-4 truncate font-mono text-xs text-slate-500">{{ prompt.key }}</p>
                </Link>
            </div>
        </main>
    </AdminLayout>
</template>
