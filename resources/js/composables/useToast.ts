import { ref } from 'vue';

export interface Toast {
    id: number;
    title: string;
    description?: string;
    variant: 'default' | 'success' | 'destructive';
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let toastId = 0;

export function useToast() {
    const addToast = (toast: Omit<Toast, 'id'>) => {
        const id = ++toastId;
        const newToast: Toast = {
            id,
            duration: 5000,
            ...toast,
        };
        toasts.value.push(newToast);

        // Auto remove after duration
        setTimeout(() => {
            removeToast(id);
        }, newToast.duration);

        return id;
    };

    const removeToast = (id: number) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (title: string, description?: string) => {
        return addToast({ title, description, variant: 'success' });
    };

    const error = (title: string, description?: string) => {
        return addToast({ title, description, variant: 'destructive' });
    };

    const info = (title: string, description?: string) => {
        return addToast({ title, description, variant: 'default' });
    };

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
    };
}
