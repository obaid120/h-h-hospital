export function initializeResponsiveTable(wrapper, view = window) {
    const region = wrapper.querySelector('.table-scroll');
    const hint = wrapper.querySelector('[data-table-hint]');
    const update = () => { hint.hidden = region.scrollWidth <= region.clientWidth + 1; };
    if (view.ResizeObserver) {
        const observer = new view.ResizeObserver(update);
        observer.observe(region);
        observer.observe(region.querySelector('table'));
    } else {
        view.addEventListener('resize', update);
    }
    update();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-table-wrapper]').forEach((wrapper) => initializeResponsiveTable(wrapper));
}
