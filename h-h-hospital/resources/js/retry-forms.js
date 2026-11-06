export function initializeRetryForm(form, environment = {}) {
    const view = environment.window ?? window;
    const send = environment.fetch ?? view.fetch.bind(view);
    const later = environment.setTimeout ?? view.setTimeout.bind(view);
    const cancel = environment.clearTimeout ?? view.clearTimeout.bind(view);
    const makePayload = environment.formData ?? ((element) => new FormData(element));
    const labels = JSON.parse(form.querySelector('[data-request-labels]').textContent);
    const feedback = form.querySelector('[data-request-feedback]');
    const session = form.querySelector('[data-request-session]');
    const resume = form.querySelector('[data-request-resume]');
    const fields = [...form.querySelectorAll('input, select, textarea')];
    const originalDisabled = new Map(fields.map((field) => [field, field.disabled]));
    const submits = [...form.querySelectorAll('button[type="submit"]')];
    const originalLabels = new Map(submits.map((button) => [button, button.querySelector('[data-button-label]')?.textContent]));
    let payload;
    let inFlight = false;
    let uncertain = false;
    let needsSession = false;
    let blocked = false;
    let completed = false;

    function render() {
        fields.forEach((field) => { field.disabled = originalDisabled.get(field) || inFlight || uncertain; });
        submits.forEach((button) => {
            button.disabled = inFlight || blocked || needsSession;
            button.setAttribute('aria-busy', String(inFlight));
            button.querySelector('[data-button-spinner]')?.classList.toggle('hidden', !inFlight);
            const label = button.querySelector('[data-button-label]');
            if (label) label.textContent = inFlight ? labels.sending : (uncertain ? labels.retry : originalLabels.get(button));
        });
        session.hidden = !needsSession;
        resume.disabled = inFlight;
    }

    function message(text) {
        feedback.textContent = text;
        feedback.hidden = !text;
        if (text) feedback.focus();
    }

    async function request(url, options) {
        const controller = new AbortController();
        const timeout = later(() => controller.abort(), 20000);
        try {
            const response = await send(url, { credentials: 'same-origin', cache: 'no-store', redirect: 'error', ...options, signal: controller.signal });
            const data = response.status === 401 || response.status === 419 || response.status === 403 ? {} : await response.json();
            return { response, data };
        } finally {
            cancel(timeout);
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (inFlight || blocked || needsSession) return;
        if (!form.noValidate && !form.checkValidity()) return;
        if (!uncertain) payload = makePayload(form);
        inFlight = true;
        message(labels.sending);
        fields.forEach((field) => { field.removeAttribute('aria-invalid'); field.classList.remove('is-invalid'); });
        render();
        try {
            const { response, data } = await request(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: payload });
            if (response.ok && data.completed === true && typeof data.redirect_url === 'string') {
                const target = new URL(data.redirect_url, view.location.href);
                if (target.origin !== view.location.origin || !target.pathname.startsWith('/staff/')) throw new Error('Invalid result');
                completed = true;
                blocked = true;
                uncertain = false;
                view.location.assign(target.href);
            } else if (response.status === 422) {
                uncertain = false;
                payload = undefined;
                const errors = Object.entries(data.errors ?? {});
                message([labels.validation, ...errors.flatMap(([, values]) => values)].join('\n'));
                fields.forEach((field) => {
                    if (errors.some(([name]) => field.name === name)) { field.setAttribute('aria-invalid', 'true'); field.classList.add('is-invalid'); }
                });
            } else if (response.status === 401 || response.status === 419) {
                uncertain = true;
                needsSession = true;
                message(labels.session);
            } else if (response.status === 403) {
                uncertain = true;
                blocked = true;
                message(labels.forbidden);
            } else if (response.status === 429) {
                message(labels.rate_limit);
            } else {
                throw new Error('Unconfirmed result');
            }
        } catch {
            uncertain = true;
            message(labels.uncertain);
        } finally {
            inFlight = false;
            render();
        }
    });

    resume.addEventListener('click', async () => {
        if (inFlight || !needsSession) return;
        inFlight = true;
        render();
        try {
            const { response, data } = await request(form.dataset.sessionUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok || typeof data.csrf_token !== 'string') throw new Error('Sign-in required');
            if (String(data.user_id) !== form.dataset.actorId) {
                message(labels.different_account);
                return;
            }
            payload.set('_token', data.csrf_token);
            form.elements.namedItem('_token').value = data.csrf_token;
            needsSession = false;
            message(labels.restored);
        } catch {
            message(labels.session);
        } finally {
            inFlight = false;
            render();
        }
    });

    view.addEventListener('beforeunload', (event) => {
        if (!completed && (uncertain || inFlight)) { event.preventDefault(); event.returnValue = ''; }
    });
    render();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-retry-form]').forEach((form) => initializeRetryForm(form));
}
