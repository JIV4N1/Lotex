// Progressive enhancement: content is always visible, even without JavaScript.
const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
const running = new Map();
let observer;

function stopMotion() {
    observer?.disconnect();
    running.forEach(animation => animation.cancel());
    running.clear();
}

function startMotion() {
    stopMotion();
    if (preference.matches || !('IntersectionObserver' in window) || !Element.prototype.animate) return;

    observer = new IntersectionObserver(entries => {
        entries.forEach(({target, isIntersecting}) => {
            if (!isIntersecting) return;
            observer.unobserve(target);
            if (target.dataset.motionSeen || target.contains(document.activeElement)) return;
            target.dataset.motionSeen = 'true';
            const column = target.closest('.row > div');
            const index = column ? [...column.parentElement.children].indexOf(column) : 0;
            const animation = target.animate([
                {opacity: 0.45, transform: 'translateY(14px)'},
                {opacity: 1, transform: 'translateY(0)'},
            ], {duration: 480, delay: Math.min(index, 2) * 65, easing: 'cubic-bezier(.22,1,.36,1)'});
            running.set(target, animation);
            animation.onfinish = () => running.delete(target);
        });
    }, {threshold: 0.08});

    document.querySelectorAll('main h1, main .section-heading, main .property-card, main .advisor-card, main .about-landscape, main .contact-strip, main .step-number').forEach(element => observer.observe(element));
}

// Keyboard interaction takes priority over decoration.
document.addEventListener('focusin', event => {
    running.forEach((animation, element) => {
        if (element.contains(event.target)) {
            animation.cancel();
            running.delete(element);
        }
    });
});
preference.addEventListener('change', startMotion);
window.addEventListener('pagehide', stopMotion);
window.addEventListener('pageshow', event => { if (event.persisted) startMotion(); });
startMotion();
