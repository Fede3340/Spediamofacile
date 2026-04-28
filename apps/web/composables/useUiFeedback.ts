/**
 * @file useUiFeedback — Composable useUiFeedback.
 */
export const useUiFeedback = () => {
    const toast = useToast();
    const push = (type, title, description = '', options = {}) => {
        const map = {
            success: { color: 'success' },
            info: { color: 'info' },
            warning: { color: 'warning' },
            error: { color: 'error' },
        };
        const preset = map[type] || map.warning;
        toast.add({
            title,
            description: description || undefined,
            color: options.color || preset.color,
            icon: options.icon ?? false,
            timeout: options.timeout ?? 4500,
        });
    };
    return {
        success: (title, description = '', options = {}) => push('success', title, description, options),
        info: (title, description = '', options = {}) => push('info', title, description, options),
        warn: (title, description = '', options = {}) => push('warning', title, description, options),
        error: (title, description = '', options = {}) => push('error', title, description, options),
        critical: (title, description = '', options = {}) => push('error', title, description, options),
    };
};
