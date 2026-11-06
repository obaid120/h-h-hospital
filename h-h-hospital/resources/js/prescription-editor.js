export function initializePrescriptionEditor(root, environment) {
    const view = environment?.window ?? window;
    const editor = root.querySelector('[data-prescription-editor]');
    const completion = root.querySelector('[data-prescription-complete]');
    const list = root.querySelector('[data-medicine-list]');
    const template = root.querySelector('[data-medicine-template]');
    const feedback = root.querySelector('[data-prescription-feedback]');
    const add = root.querySelector('[data-add-medicine]');
    const savedActions = root.querySelectorAll('[data-prescription-saved-action]');
    let dirty = root.dataset.unsaved === 'true';
    let submitting = false;

    function update() {
        feedback.textContent = root.dataset[dirty ? 'unsavedText' : 'cleanText'];
        savedActions.forEach((action) => {
            if (action.tagName === 'BUTTON') action.disabled = dirty;
            action.setAttribute('aria-disabled', dirty ? 'true' : 'false');
        });
    }

    function changed() {
        dirty = true;
        update();
    }

    function renumber() {
        const rows = [...list.querySelectorAll('[data-medicine-row]')];
        rows.forEach((row, index) => {
            row.querySelector('[data-medicine-heading]').textContent = root.dataset.rowLabel.replace('{number}', String(index + 1));
            row.querySelectorAll('[data-medicine-field]').forEach((field) => {
                const id = `medicine-${index}-${field.dataset.medicineField}`;
                const label = row.querySelector(`label[for="${field.id}"]`);
                const error = row.querySelector(`#${field.id}-error`);
                if (label) label.htmlFor = id;
                if (error) {
                    error.id = `${id}-error`;
                    field.setAttribute('aria-describedby', error.id);
                }
                field.id = id;
                field.name = `items[${index}][${field.dataset.medicineField}]`;
            });
        });
        add.disabled = rows.length >= 30;
    }

    editor.addEventListener('input', changed);
    editor.addEventListener('change', changed);
    add.addEventListener('click', () => {
        if (list.querySelectorAll('[data-medicine-row]').length >= 30) {
            feedback.textContent = root.dataset.limitText;
            return;
        }
        const row = template.content.cloneNode(true).firstElementChild;
        row.querySelectorAll('[data-medicine-field]').forEach((field) => {
            field.value = '';
            field.classList.remove('is-invalid');
            field.setAttribute('aria-invalid', 'false');
            field.removeAttribute('aria-describedby');
        });
        row.querySelectorAll('.field-error').forEach((error) => error.remove());
        list.append(row);
        renumber();
        changed();
        row.querySelector('[data-medicine-field]').focus();
    });
    root.addEventListener('click', (event) => {
        const remove = event.target.closest?.('[data-remove-medicine]');
        if (remove && list.contains(remove)) {
            remove.closest('[data-medicine-row]').remove();
            renumber();
            changed();
            add.focus();
        }
        if (dirty && event.target.closest?.('[data-prescription-saved-action]')) {
            event.preventDefault();
            feedback.textContent = root.dataset.unsavedText;
        }
    });
    function submit(event) {
        if (event.defaultPrevented) return;
        if (event.target === completion && dirty) {
            event.preventDefault();
            feedback.textContent = root.dataset.unsavedText;
            return;
        }
        submitting = true;
    }
    editor.addEventListener('submit', submit);
    completion?.addEventListener('submit', submit);
    view.addEventListener('beforeunload', (event) => {
        if (dirty && !submitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
    view.addEventListener('pageshow', () => { submitting = false; });
    renumber();
    update();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-prescription-workspace]').forEach((root) => initializePrescriptionEditor(root));
}
