const formStates = new WeakMap();
const cachedForms = new Map();
const initializedForms = new Set();
let attentionForm = null;
let openVersion = 0;

const dateLabel = (date) => new Intl.DateTimeFormat(document.documentElement.lang || 'en', { dateStyle: 'full', timeZone: 'UTC' }).format(new Date(`${date}T12:00:00Z`));
const monthLabel = (month) => new Intl.DateTimeFormat(document.documentElement.lang || 'en', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${month}-01T12:00:00Z`));
const interpolate = (message, values) => Object.entries(values).reduce((text, [key, value]) => text.replaceAll(`:${key}`, String(value)), message);

async function fetchWithTimeout(url, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 15000);
    try {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options, signal: controller.signal });
        const body = await response.arrayBuffer();
        return new Response(body, { status: response.status, statusText: response.statusText, headers: response.headers });
    } finally {
        clearTimeout(timer);
    }
}

function feedback(form, message) {
    const region = form.querySelector('[data-booking-feedback]');
    region.textContent = message;
    region.hidden = !message;
}

function clearErrors(form) {
    form.querySelectorAll('[data-booking-error]').forEach((region) => { region.hidden = true; region.textContent = ''; });
    form.querySelectorAll('[aria-invalid=true]').forEach((input) => { input.removeAttribute('aria-invalid'); input.classList.remove('is-invalid'); });
}

function fieldErrors(form, errors) {
    Object.entries(errors || {}).forEach(([name, messages]) => {
        const region = [...form.querySelectorAll('[data-booking-error]')].find((element) => element.dataset.bookingError === name);
        if (region) { region.hidden = false; region.textContent = messages[0]; }
        const input = form.elements.namedItem(name);
        if (input instanceof HTMLInputElement) { input.setAttribute('aria-invalid', 'true'); input.classList.add('is-invalid'); }
    });
}

function controls(form) {
    const state = formStates.get(form);
    const locked = state.inFlight || state.uncertain || state.blocked;
    ['name', 'phone', 'age_years', 'date'].forEach((name) => { form.elements.namedItem(name).disabled = locked; });
    form.querySelectorAll('[data-month-step]').forEach((button) => {
        const boundary = Number(button.dataset.monthStep) < 0 ? state.calendar.month <= state.calendar.first_month : state.calendar.month >= state.calendar.last_month;
        button.disabled = locked || state.monthBusy || boundary;
    });
    form.querySelectorAll('[data-calendar-date]').forEach((button) => { button.disabled = locked || state.monthBusy || button.dataset.calendarState !== 'available'; });
    form.querySelector('[data-calendar-retry]').disabled = locked || state.monthBusy;
    const submit = form.querySelector('[data-booking-submit]');
    submit.disabled = state.inFlight || state.blocked || !form.elements.namedItem('date').value;
    submit.setAttribute('aria-busy', state.inFlight ? 'true' : 'false');
    submit.querySelector('[data-button-spinner]').classList.toggle('hidden', !state.inFlight);
    submit.querySelector('[data-button-label]').textContent = state.inFlight ? state.labels.submitting : (state.uncertain ? state.labels.retry : state.labels.submit);
}

function selection(form) {
    const state = formStates.get(form);
    const value = form.elements.namedItem('date').value;
    const selected = state.calendar.days.find((day) => day.date === value);
    if (selected && selected.state !== 'available' && !state.uncertain && !state.inFlight) {
        form.elements.namedItem('date').value = '';
    }
    const chosen = form.elements.namedItem('date').value;
    form.querySelector('[data-selected-date]').textContent = chosen ? `${state.labels.selected}: ${dateLabel(chosen)}${selected?.state === 'available' ? ` · ${selected.opens_at} – ${selected.closes_at}` : ''}` : state.labels.select_date;
    form.querySelectorAll('[data-calendar-date]').forEach((button) => button.setAttribute('aria-pressed', button.dataset.calendarDate === chosen ? 'true' : 'false'));
    controls(form);
}

function renderCalendar(form) {
    const state = formStates.get(form);
    form.querySelector('[data-calendar-month]').textContent = monthLabel(state.calendar.month);
    const grid = form.querySelector('[data-calendar-days]');
    const nodes = [];
    const weekday = new Date(`${state.calendar.month}-01T12:00:00Z`).getUTCDay();
    for (let i = 0; i < (weekday + 6) % 7; i++) {
        const blank = document.createElement('span');
        blank.setAttribute('aria-hidden', 'true');
        nodes.push(blank);
    }
    state.calendar.days.forEach((day) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'booking-day';
        button.dataset.calendarDate = day.date;
        button.dataset.calendarState = day.state;
        button.setAttribute('aria-label', interpolate(day.state === 'available' ? state.labels.available_label : state.labels.date_label, { date: dateLabel(day.date), count: day.remaining_seats, state: state.labels.states[day.state] }));
        const number = document.createElement('span');
        number.textContent = String(Number(day.date.slice(-2)));
        const hint = document.createElement('small');
        hint.textContent = day.state === 'available' ? interpolate(state.labels.seats_left, { count: day.remaining_seats }) : (['outside_horizon', 'inactive'].includes(day.state) ? '—' : state.labels.states[day.state]);
        button.append(number, hint);
        nodes.push(button);
    });
    grid.replaceChildren(...nodes);
    selection(form);
}

async function loadMonth(form, month) {
    const state = formStates.get(form);
    state.monthBusy = true;
    const region = form.querySelector('[data-calendar-feedback]');
    region.textContent = state.labels.calendar_loading;
    form.querySelector('[data-calendar-days]').setAttribute('aria-busy', 'true');
    controls(form);
    try {
        const url = new URL(form.dataset.monthUrl, location.href);
        url.searchParams.set('month', month);
        const response = await fetchWithTimeout(url, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.calendar) throw new Error('Calendar unavailable');
        state.calendar = data.calendar;
        region.textContent = '';
        renderCalendar(form);
        document.dispatchEvent(new CustomEvent('ui:reveal', { detail: { element: form.querySelector('[data-calendar-days]') } }));
    } catch {
        region.textContent = state.labels.calendar_error;
    } finally {
        state.monthBusy = false;
        form.querySelector('[data-calendar-days]').removeAttribute('aria-busy');
        controls(form);
    }
}

function payload(form) {
    return Object.fromEntries(['doctor_id', 'date', 'name', 'phone', 'age_years', 'request_key'].map((name) => [name, form.elements.namedItem(name).value]));
}

function initialize(form) {
    if (formStates.has(form)) return;
    const state = { calendar: JSON.parse(form.querySelector('[data-calendar-initial]').textContent), labels: JSON.parse(form.querySelector('[data-booking-labels]').textContent), inFlight: false, monthBusy: false, uncertain: form.dataset.uncertain === 'true', blocked: false, completed: false, submitted: null };
    formStates.set(form, state);
    initializedForms.add(form);
    if (state.uncertain) { state.submitted = payload(form); attentionForm = form; }
    form.querySelector('[data-booking-calendar]').hidden = false;
    form.querySelector('[data-date-fallback]').hidden = true;
    renderCalendar(form);
    form.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        const date = event.target.closest('[data-calendar-date]');
        if (date && !date.disabled) {
            form.elements.namedItem('date').value = date.dataset.calendarDate;
            const error = form.querySelector('[data-booking-error=date]');
            error.hidden = true;
            selection(form);
        }
        const navigation = event.target.closest('[data-month-step]');
        if (navigation && !navigation.disabled) {
            const next = new Date(`${state.calendar.month}-01T12:00:00Z`);
            next.setUTCMonth(next.getUTCMonth() + Number(navigation.dataset.monthStep));
            loadMonth(form, next.toISOString().slice(0, 7));
        }
        if (event.target.closest('[data-calendar-retry]')) loadMonth(form, state.calendar.month);
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.inFlight || state.blocked) return;
        clearErrors(form);
        feedback(form, '');
        state.submitted = state.uncertain ? state.submitted : payload(form);
        state.inFlight = true;
        attentionForm = form;
        controls(form);
        try {
            const response = await fetchWithTimeout(form.dataset.submitUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.elements.namedItem('_token').value }, body: JSON.stringify(state.submitted) });
            const data = response.status === 419 ? {} : await response.json();
            if (response.ok && data.booking && data.confirmation_url) {
                const target = new URL(data.confirmation_url, location.href);
                if (target.origin !== location.origin) throw new Error('Invalid result');
                state.blocked = true;
                state.completed = true;
                attentionForm = null;
                cachedForms.delete(form.dataset.formUrl);
                form.closest('dialog')?.close();
                location.assign(target.href);
                return;
            }
            if (response.status === 422) {
                state.uncertain = false;
                attentionForm = null;
                fieldErrors(form, data.errors);
                feedback(form, state.labels.validation);
            } else if (response.status === 409 && data.error?.code !== 'request_conflict') {
                state.uncertain = false;
                attentionForm = null;
                feedback(form, data.error.message);
                form.elements.namedItem('date').value = '';
                await loadMonth(form, state.calendar.month);
            } else if (response.status === 409) {
                state.blocked = true;
                feedback(form, data.error.message);
            } else if (response.status === 429) {
                feedback(form, data.error?.message || state.labels.errors.rate_limited);
                if (!state.uncertain) attentionForm = null;
            } else if (response.status === 419) {
                state.uncertain = true;
                state.blocked = true;
                feedback(form, state.labels.session_expired);
            } else {
                throw new Error('Unconfirmed result');
            }
        } catch {
            state.uncertain = true;
            feedback(form, state.labels.errors.temporarily_unavailable);
        } finally {
            state.inFlight = false;
            selection(form);
            const message = form.querySelector('[data-booking-feedback]');
            if (!message.hidden && form.isConnected) message.focus({ preventScroll: false });
        }
    });
}

document.querySelectorAll('[data-booking-form]').forEach(initialize);

document.addEventListener('click', async (event) => {
    if (!(event.target instanceof Element) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    const trigger = event.target.closest('[data-booking-url]');
    if (!trigger) return;
    const panel = document.querySelector('[data-booking-panel]');
    if (!panel) return;
    event.preventDefault();
    const version = ++openVersion;
    const previous = panel.querySelector('[data-booking-form]');
    if (previous && !formStates.get(previous)?.completed) cachedForms.set(previous.dataset.formUrl, previous);
    const preserved = attentionForm || cachedForms.get(trigger.dataset.bookingUrl);
    if (preserved) {
        panel.replaceChildren(preserved);
        requestAnimationFrame(() => preserved.querySelector('[data-booking-focus]').focus());
        return;
    }
    const loading = document.createElement('p');
    loading.className = 'text-sm text-muted';
    loading.setAttribute('role', 'status');
    loading.textContent = panel.dataset.loadingLabel;
    panel.replaceChildren(loading);
    try {
        const response = await fetchWithTimeout(trigger.dataset.bookingUrl, { headers: { 'X-Booking-Fragment': '1', Accept: 'text/html' } });
        if (!response.ok) throw new Error('Form unavailable');
        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
        const form = parsed.querySelector('[data-booking-form]');
        if (!form) throw new Error('Missing form');
        if (version !== openVersion) return;
        panel.replaceChildren(form);
        initialize(form);
        cachedForms.set(trigger.dataset.bookingUrl, form);
        form.querySelector('[data-booking-focus]').focus();
        document.dispatchEvent(new CustomEvent('ui:reveal', { detail: { element: form } }));
    } catch {
        if (version !== openVersion) return;
        const message = document.createElement('p');
        message.className = 'text-sm leading-6 text-muted';
        message.textContent = panel.dataset.errorLabel;
        const fallback = document.createElement('a');
        fallback.href = trigger.href;
        fallback.className = 'button button-secondary mt-5';
        fallback.textContent = panel.dataset.openPageLabel;
        panel.replaceChildren(message, fallback);
    }
});

window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    initializedForms.forEach((form) => {
        const state = formStates.get(form);
        if (state.inFlight) { state.inFlight = false; state.uncertain = true; attentionForm = form; feedback(form, state.labels.errors.temporarily_unavailable); }
        controls(form);
    });
});
