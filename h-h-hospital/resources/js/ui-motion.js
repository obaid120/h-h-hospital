export function initializeMotion(doc, view, animate) {
    const reduced = view.matchMedia('(prefers-reduced-motion: reduce)');
    const printing = view.matchMedia('print');
    const active = new Map();
    const seen = new WeakSet();

    function reveal(element, delay = 0) {
        if (!element || reduced.matches || printing.matches) return;
        active.get(element)?.complete();
        try {
            const animation = animate(element, { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { duration: 0.18, delay });
            active.set(element, animation);
            Promise.resolve(animation).finally(() => { if (active.get(element) === animation) active.delete(element); }).catch(() => {});
        } catch {
            element.style.opacity = '1';
            element.style.transform = 'none';
        }
    }

    function finish() {
        active.forEach((animation, element) => { animation.complete(); element.style.opacity = '1'; element.style.transform = 'none'; });
        active.clear();
    }

    const observer = view.IntersectionObserver ? new view.IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting || seen.has(target)) return;
            seen.add(target);
            observer.unobserve(target);
            const index = [...(target.parentElement?.children ?? [])].indexOf(target);
            reveal(target, target.hasAttribute('data-doctor-card') ? Math.max(0, index % 3) * 0.04 : 0);
        });
    }, { threshold: 0.08 }) : null;
    doc.querySelectorAll('[data-motion-enter]').forEach((element) => observer ? observer.observe(element) : reveal(element));
    reduced.addEventListener('change', (event) => { if (event.matches) finish(); });
    view.addEventListener('beforeprint', finish);
    doc.addEventListener('ui:reveal', (event) => reveal(event.detail?.element));
    return { reveal, finish };
}
