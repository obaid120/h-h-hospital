export function initializeDoctorQueue(root, environment) {
    const doc = environment?.document ?? document;
    const fetchQueue = environment?.fetch ?? window.fetch.bind(window);
    const later = environment?.setTimeout ?? window.setTimeout.bind(window);
    const cancel = environment?.clearTimeout ?? window.clearTimeout.bind(window);
    const live = root.querySelector('[data-queue-live]');
    const feedback = root.querySelector('[data-queue-feedback]');
    const reload = root.querySelector('[data-queue-reload]');
    let timer;
    let request;
    let generation = 0;
    let stopped = false;
    let consultationChanged = false;

    function message(key) {
        feedback.textContent = root.dataset[key];
    }

    function blockCalls() {
        live.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
        reload.hidden = false;
    }

    function pause() {
        cancel(timer);
        generation++;
        request?.abort();
        request = undefined;
    }

    function schedule() {
        cancel(timer);
        if (!stopped && !doc.hidden) timer = later(refresh, 10000);
    }

    async function refresh() {
        if (stopped || doc.hidden || request) return;
        cancel(timer);
        const controller = new AbortController();
        request = controller;
        const currentGeneration = ++generation;
        const timeout = later(() => controller.abort(), 12000);
        message('loadingText');
        try {
            const response = await fetchQueue(root.dataset.refreshUrl, {
                method: 'GET', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal,
            });
            if (currentGeneration !== generation || doc.hidden) return;
            if (response.status === 401 || response.status === 403) {
                stopped = true;
                live.replaceChildren();
                blockCalls();
                message('sessionText');
                return;
            }
            if (!response.ok) throw new Error('Queue refresh failed');
            const state = await response.json();
            if (currentGeneration !== generation || doc.hidden) return;
            if (typeof state.html !== 'string' || typeof state.date !== 'string') throw new Error('Invalid queue response');
            if (state.date !== root.dataset.date) {
                stopped = true;
                blockCalls();
                message('dateText');
                return;
            }
            if (live.contains(doc.activeElement)) {
                message('updatedText');
                return;
            }
            consultationChanged ||= String(state.current_id ?? '') !== root.dataset.currentId;
            live.innerHTML = state.html;
            if (consultationChanged) {
                reload.hidden = false;
                message('changedText');
            } else {
                message('updatedText');
            }
        } catch {
            if (currentGeneration === generation && !doc.hidden) message('errorText');
        } finally {
            cancel(timeout);
            if (currentGeneration === generation) {
                request = undefined;
                schedule();
            }
        }
    }

    root.querySelector('[data-queue-refresh]').addEventListener('click', refresh);
    root.addEventListener('submit', () => { stopped = true; pause(); });
    doc.addEventListener('visibilitychange', () => {
        if (doc.hidden) pause();
        else refresh();
    });
    (environment?.window ?? window).addEventListener('pagehide', () => { stopped = true; pause(); });
    schedule();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-doctor-queue]').forEach((root) => initializeDoctorQueue(root));
}
