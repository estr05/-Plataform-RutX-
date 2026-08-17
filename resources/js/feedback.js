const FEEDBACK_TYPES = new Set(['success', 'info', 'warning', 'error']);
const ACTION_EVENT_PATTERN = /^[a-z][a-z0-9._-]{0,79}$/;

/**
 * Sistema de feedback efímero de RutX Web.
 *
 * La API pública se expone como window.RutXFeedback. Los mensajes se insertan
 * con textContent, nunca como HTML, para evitar XSS al mostrar errores remotos.
 */
class FeedbackManager {
    constructor(stack) {
        this.stack = stack;
        this.activeTimer = null;
        this.activeId = null;
    }

    show(rawFeedback) {
        const feedback = normalizeFeedback(rawFeedback);

        if (feedback === null) {
            return null;
        }

        this.dismiss();

        const id = window.crypto?.randomUUID?.() ?? `feedback-${Date.now()}`;
        const toast = createToast(feedback, id, () => this.dismiss(id));

        this.activeId = id;
        this.stack.replaceChildren(toast);

        if (feedback.duration > 0) {
            this.activeTimer = window.setTimeout(() => this.dismiss(id), feedback.duration);
        }

        return id;
    }

    dismiss(id = this.activeId) {
        if (id === null || id !== this.activeId) {
            return;
        }

        if (this.activeTimer !== null) {
            window.clearTimeout(this.activeTimer);
        }

        this.activeTimer = null;
        this.activeId = null;
        this.stack.replaceChildren();
    }
}

function normalizeFeedback(rawFeedback) {
    if (typeof rawFeedback !== 'object' || rawFeedback === null) {
        return null;
    }

    const type = rawFeedback.type;
    const message = rawFeedback.message;
    const title = rawFeedback.title ?? null;
    const duration = rawFeedback.duration ?? 3_000;
    const action = rawFeedback.action ?? null;

    if (
        typeof type !== 'string'
        || !FEEDBACK_TYPES.has(type)
        || typeof message !== 'string'
        || message.trim() === ''
        || (title !== null && (typeof title !== 'string' || title.trim() === ''))
        || !Number.isInteger(duration)
        || duration < 1_000
        || duration > 30_000
    ) {
        return null;
    }

    let normalizedAction = null;

    if (action !== null) {
        if (
            typeof action !== 'object'
            || typeof action.label !== 'string'
            || action.label.trim() === ''
            || typeof action.event !== 'string'
            || !ACTION_EVENT_PATTERN.test(action.event)
        ) {
            return null;
        }

        normalizedAction = {
            label: action.label.trim(),
            event: action.event,
        };
    }

    return {
        type,
        title: typeof title === 'string' ? title.trim() : null,
        message: message.trim(),
        duration,
        action: normalizedAction,
    };
}

function createToast(feedback, id, onClose) {
    const toast = document.createElement('article');
    toast.className = `rutx-feedback-toast rutx-feedback-toast--${feedback.type}`;
    toast.dataset.feedbackId = id;
    toast.setAttribute('role', feedback.type === 'error' ? 'alert' : 'status');

    const icon = createIcon(feedback.type);
    icon.classList.add('rutx-feedback-toast__icon');
    icon.setAttribute('aria-hidden', 'true');
    toast.append(icon);

    const content = document.createElement('div');
    content.className = 'rutx-feedback-toast__content';

    if (feedback.title !== null) {
        const title = document.createElement('p');
        title.className = 'rutx-feedback-toast__title';
        title.textContent = feedback.title;
        content.append(title);
    }

    const message = document.createElement('p');
    message.className = 'rutx-feedback-toast__message';
    message.textContent = feedback.message;
    content.append(message);

    if (feedback.action !== null) {
        const actionButton = document.createElement('button');
        actionButton.type = 'button';
        actionButton.className = 'rutx-feedback-toast__action';
        actionButton.textContent = feedback.action.label;
        actionButton.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('rutx:feedback-action', {
                detail: {
                    event: feedback.action.event,
                    feedbackId: id,
                },
            }));
            onClose();
        });
        content.append(actionButton);
    }

    toast.append(content);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'rutx-feedback-toast__close';
    closeButton.setAttribute('aria-label', 'Cerrar notificación');
    closeButton.append(createCloseIcon());
    closeButton.addEventListener('click', onClose);
    toast.append(closeButton);

    return toast;
}

function createIcon(type) {
    const svg = createSvg();

    const paths = {
        success: [
            'M9 12.75 11.25 15 15 9.75',
            'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        info: [
            'M11.25 10.5h.008v.008h-.008V10.5ZM12 12v4.5',
            'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        warning: [
            'M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.949 3.374H4.646c-1.732 0-2.815-1.874-1.949-3.374L10.051 3.38c.866-1.5 3.032-1.5 3.898 0l7.354 12.746ZM12 15.75h.008v.008H12v-.008Z',
        ],
        error: [
            'm14.25 9-4.5 4.5m0-4.5 4.5 4.5',
            'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
    };

    paths[type].forEach((pathData) => svg.append(createPath(pathData)));

    return svg;
}

function createCloseIcon() {
    const svg = createSvg();
    svg.append(createPath('M6 18 18 6M6 6l12 12'));
    return svg;
}

function createSvg() {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.8');
    return svg;
}

function createPath(pathData) {
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('d', pathData);
    return path;
}

function getInitialFeedback(stack) {
    const serializedFeedback = stack.dataset.initialFeedback;

    if (serializedFeedback === undefined || serializedFeedback === '') {
        return null;
    }

    try {
        return JSON.parse(serializedFeedback);
    } catch {
        return null;
    }
}

function initializeFeedback() {
    const stack = document.querySelector('[data-rutx-feedback-stack]');

    if (stack === null) {
        return;
    }

    const manager = new FeedbackManager(stack);

    window.RutXFeedback = Object.freeze({
        show: (feedback) => manager.show(feedback),
        success: (message) => manager.show({ type: 'success', message, duration: 3_000, action: null }),
        info: (message, action = null) => manager.show({ type: 'info', message, duration: 3_000, action }),
        warning: (message, options = {}) => manager.show({
            type: 'warning',
            message,
            title: options.title ?? null,
            duration: options.duration ?? 5_000,
            action: options.action ?? null,
        }),
        error: (message, action = null) => manager.show({
            type: 'error',
            title: 'Error',
            message,
            duration: 5_000,
            action,
        }),
        dismiss: () => manager.dismiss(),
    });

    const initialFeedback = getInitialFeedback(stack);

    if (initialFeedback !== null) {
        manager.show(initialFeedback);
    }

    window.addEventListener('rutx:feedback', (event) => manager.show(event.detail));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFeedback, { once: true });
} else {
    initializeFeedback();
}
