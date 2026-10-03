import { http, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export interface AdminHttpErrorNotice {
    message: string;
    status: number | null;
    requestId: string | null;
}

const currentNotice = ref<AdminHttpErrorNotice | null>(null);

function messageForStatus(status: number): string {
    if (status === 401) {
        return 'Sua sessão expirou. Entre novamente para continuar.';
    }

    if (status === 403) {
        return 'Você não tem permissão para realizar esta ação.';
    }

    if (status === 419) {
        return 'Sua sessão expirou. Atualize a página e tente novamente.';
    }

    if (status === 429) {
        return 'Muitas solicitações em sequência. Aguarde um pouco e tente novamente.';
    }

    if (status >= 500) {
        return 'Ocorreu um erro no servidor. Tente novamente em instantes.';
    }

    return 'Não foi possível concluir a solicitação.';
}

function showHttpError(status: number, headers: Record<string, string>): void {
    currentNotice.value = {
        message: messageForStatus(status),
        status,
        requestId: headers['x-request-id'] ?? null,
    };
}

function showNetworkError(): void {
    currentNotice.value = {
        message: 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.',
        status: null,
        requestId: null,
    };
}

export function configureAdminHttpErrors(): void {
    http.onRequest((config) => {
        currentNotice.value = null;

        return {
            ...config,
            headers: {
                ...config.headers,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
    });

    http.onError((error) => {
        if ('response' in error) {
            showHttpError(error.response.status, error.response.headers);
        } else if (error.name !== 'HttpCancelledError') {
            showNetworkError();
        }
    });

    router.on('httpException', (event) => {
        showHttpError(event.detail.response.status, event.detail.response.headers);
    });

    router.on('networkError', showNetworkError);
}

export function useAdminHttpErrors(): {
    currentNotice: typeof currentNotice;
    dismiss: () => void;
} {
    return {
        currentNotice,
        dismiss: () => {
            currentNotice.value = null;
        },
    };
}
