document.addEventListener('DOMContentLoaded', function () {

    // 1. Scroll Animations (Intersection Observer)
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1 // Uruchom gdy 10% elementu jest widoczne
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Animuj tylko raz
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.jm-animate-up');
    animatedElements.forEach(el => observer.observe(el));

    // 2. Smooth Scroll for Anchors
    document.querySelectorAll('a.jm-scroll-link').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // 3. Formularz Focus (UX)
    const inputs = document.querySelectorAll('.jm-modern-form input, .jm-modern-form textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            const wrap = input.closest('.jm-field-wrap');
            if (wrap) wrap.style.transform = 'translateY(-2px)';
        });
        input.addEventListener('blur', () => {
            const wrap = input.closest('.jm-field-wrap');
            if (wrap) wrap.style.transform = 'translateY(0)';
        });
    });
});