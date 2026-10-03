<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../../shared/layouts/AdminLayout.vue';

interface PromptVersion {
    id: number;
    version: number;
    status: 'draft' | 'published';
    content: string;
    created_by: string | null;
    published_by: string | null;
    created_at: string | null;
    published_at: string | null;
}

interface Prompt {
    key: string;
    title: string;
    description: string;
    default_content: string;
    content: string;
    versions: PromptVersion[];
}

const props = defineProps<{ prompt: Prompt }>();
const pendingVersionIds = ref<number[]>([]);

function publishVersion(version: PromptVersion): void {
    if (!window.confirm(`Publicar globalmente a versão v${version.version} de "${props.prompt.title}"?`)) {
        return;
    }

    pendingVersionIds.value.push(version.id);
    router.post(`/admin/mcp/prompt-versions/${version.id}/publish`, {}, {
        onFinish: () => {
            pendingVersionIds.value = pendingVersionIds.value.filter((id) => id !== version.id);
        },
    });
}

function restoreVersion(version: PromptVersion): void {
    if (!window.confirm(`Restaurar o conteúdo da versão v${version.version} e publicá-lo como uma nova versão?`)) {
        return;
    }

    pendingVersionIds.value.push(version.id);
    router.post(`/admin/mcp/prompt-versions/${version.id}/restore`, {}, {
        onFinish: () => {
            pendingVersionIds.value = pendingVersionIds.value.filter((id) => id !== version.id);
        },
    });
}
</script>

<template>
    <Head :title="prompt.title" />
    <AdminLayout>
        <main class="mx-auto max-w-5xl px-6 py-10">
            <Link class="text-sm text-slate-600 hover:text-slate-900" href="/admin/mcp/prompts">← Prompts MCP</Link>
            <h1 class="mt-4 text-2xl font-semibold tracking-tight">{{ prompt.title }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ prompt.description }}</p>
            <p class="mt-2 font-mono text-xs text-slate-500">{{ prompt.key }}</p>

            <section class="mt-6 rounded-lg border border-slate-200 p-5">
                <h2 class="font-semibold">Editar conteúdo</h2>
                <p class="mt-1 text-sm text-slate-600">Use os marcadores de variáveis documentados no prompt padrão quando aplicável. Salvar cria um rascunho versionado.</p>
                <Form class="mt-4" :action="`/admin/mcp/prompts/${prompt.key}/draft`" method="post" #default="{ errors, processing }">
                    <textarea
                        class="min-h-[26rem] w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm leading-6"
                        name="content"
                        :value="prompt.content"
                        maxlength="100000"
                        required
                    />
                    <p v-if="errors.content" class="mt-1.5 text-sm text-red-700">{{ errors.content }}</p>
                    <button class="mt-3 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60" type="submit" :disabled="processing">
                        {{ processing ? 'Salvando…' : 'Salvar rascunho' }}
                    </button>
                </Form>
            </section>

            <details class="mt-4 rounded-lg border border-slate-200 p-5">
                <summary class="cursor-pointer font-medium">Ver prompt padrão do código</summary>
                <pre class="mt-4 whitespace-pre-wrap font-mono text-xs leading-5 text-slate-600">{{ prompt.default_content }}</pre>
            </details>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">Histórico de versões</h2>
                <div v-if="prompt.versions.length" class="mt-4 space-y-3">
                    <details v-for="version in prompt.versions" :key="version.id" class="rounded-lg border border-slate-200 p-4">
                        <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-3">
                            <span class="font-medium">Versão v{{ version.version }}</span>
                            <span :class="version.status === 'published' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'" class="rounded-full px-2.5 py-1 text-xs font-medium">
                                {{ version.status === 'published' ? 'Publicada' : 'Rascunho' }}
                            </span>
                        </summary>
                        <p class="mt-2 text-xs text-slate-500">Criada por {{ version.created_by ?? '—' }} em {{ version.created_at ?? '—' }}</p>
                        <pre class="mt-3 max-h-80 overflow-auto whitespace-pre-wrap rounded bg-slate-50 p-3 font-mono text-xs leading-5">{{ version.content }}</pre>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-if="version.status === 'draft'"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-60"
                                type="button"
                                :disabled="pendingVersionIds.includes(version.id)"
                                @click="publishVersion(version)"
                            >
                                {{ pendingVersionIds.includes(version.id) ? 'Publicando…' : 'Publicar esta versão' }}
                            </button>
                            <button
                                v-else
                                class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium disabled:opacity-60"
                                type="button"
                                :disabled="pendingVersionIds.includes(version.id)"
                                @click="restoreVersion(version)"
                            >
                                {{ pendingVersionIds.includes(version.id) ? 'Restaurando…' : 'Restaurar como nova versão' }}
                            </button>
                        </div>
                    </details>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">Ainda não há versões salvas. O MCP está usando o prompt padrão do código.</p>
            </section>
        </main>
    </AdminLayout>
</template>
