<script setup lang="ts">
import { useAdminHttpErrors } from '../../core/http/adminHttpErrors';

const { currentNotice, dismiss } = useAdminHttpErrors();
</script>

<template>
    <div
        v-if="currentNotice"
        class="fixed right-6 top-6 z-50 w-[min(28rem,calc(100vw-3rem))] rounded-lg border border-red-200 bg-white p-4 text-slate-900 shadow-lg"
        role="alert"
        aria-live="assertive"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="font-medium">{{ currentNotice.message }}</p>
                <p v-if="currentNotice.requestId" class="mt-1 text-xs text-slate-500">
                    Referência: {{ currentNotice.requestId }}
                </p>
            </div>
            <button
                class="rounded px-2 py-1 text-sm text-slate-600 hover:bg-slate-100"
                type="button"
                aria-label="Fechar aviso"
                @click="dismiss"
            >
                Fechar
            </button>
        </div>
    </div>
</template>
