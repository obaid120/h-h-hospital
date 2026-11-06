document.querySelectorAll('[data-walk-in-fees]').forEach((form) => {
    const doctor = form.querySelector('select[name="doctor_id"]');
    const priority = form.querySelector('input[type="checkbox"][name="is_priority"]');
    const summary = form.querySelector('[data-fee-summary]');
    function update() {
        const option = doctor.selectedOptions[0];
        summary.hidden = !option?.value;
        if (summary.hidden) return;
        form.querySelector('[data-fee-base]').textContent = option.dataset.normal;
        form.querySelector('[data-fee-priority]').textContent = priority.checked ? option.dataset.surcharge : '—';
        form.querySelector('[data-fee-total]').textContent = priority.checked ? option.dataset.priorityTotal : option.dataset.normal;
    }
    doctor.addEventListener('change', update);
    priority.addEventListener('change', update);
    update();
});
