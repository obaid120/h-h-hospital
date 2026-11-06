import { animate } from 'motion/mini';
import './booking';
import './doctor-queue';
import './walk-in-fees';
import './prescription-editor';
import './retry-forms';
import './responsive-tables';
import { initializeMotion } from './ui-motion';

const dialogTriggers = new WeakMap();
const motion = initializeMotion(document, window, animate);

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;

    if (event.target.closest('[data-print]')) window.print();
    if (event.target.closest('[data-history-back]')) window.history.back();

    const trigger = event.target.closest('[data-open-dialog]');
    if (trigger && !(trigger instanceof HTMLAnchorElement && (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0))) {
        const dialog = document.getElementById(trigger.dataset.openDialog);
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            event.preventDefault();
            dialogTriggers.set(dialog, trigger);
            dialog.showModal();
            document.documentElement.classList.add('dialog-open');
            const panel = dialog.querySelector('[data-dialog-panel]');
            if (panel) motion.reveal(panel);
        }
    }

    const closeButton = event.target.closest('[data-close-dialog]');
    if (closeButton) closeButton.closest('dialog')?.close();

    const dismissButton = event.target.closest('[data-dismiss-notification]');
    if (dismissButton) {
        dismissButton.closest('[data-notification]')?.remove();
        document.getElementById('main-content')?.focus({ preventScroll: true });
    }

    document.querySelectorAll('.public-menu[open]').forEach((menu) => {
        if (!menu.contains(event.target) || event.target.closest('a')) menu.open = false;
    });
});

document.querySelectorAll('dialog[data-dialog]').forEach((dialog) => {
    dialog.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') return;
        const focusable = [...dialog.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])')]
            .filter((element) => element.getClientRects().length && !element.closest('[hidden], [inert]'));
        const first = focusable[0];
        const last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
            dialog.close();
        }
    });

    dialog.addEventListener('close', () => {
        document.documentElement.classList.toggle('dialog-open', Boolean(document.querySelector('dialog[open]')));
        const trigger = dialogTriggers.get(dialog);
        if (trigger?.getClientRects().length) trigger.focus({ preventScroll: true });
        else document.getElementById('main-content')?.focus({ preventScroll: true });
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.public-menu[open]').forEach((menu) => {
        menu.open = false;
        menu.querySelector('summary')?.focus();
    });
});

window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
    const drawer = document.getElementById('staff-navigation');
    if (event.matches && drawer instanceof HTMLDialogElement && drawer.open) drawer.close();
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-loading-form]')) return;
    if (event.defaultPrevented) return;
    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }
    if (!form.noValidate && !form.checkValidity()) return;

    form.dataset.submitting = 'true';
    form.querySelectorAll('button[type="submit"]:not(:disabled)').forEach((button) => {
        button.dataset.submitLoading = 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.querySelector('[data-button-spinner]')?.classList.remove('hidden');
    });
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted && document.body.hasAttribute('data-staff-session')) {
        window.location.reload();
        return;
    }

    document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
        delete form.dataset.submitting;
        form.querySelectorAll('[data-submit-loading="true"]').forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            delete button.dataset.submitLoading;
            button.querySelector('[data-button-spinner]')?.classList.add('hidden');
        });
    });
});
