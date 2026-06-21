(function() {
    // A11y: język dokumentu (fallback, gdy motyw nie ustawia lang na <html>)
    if (!document.documentElement.lang) {
        document.documentElement.lang = 'pl';
    }

    // A11y: utwórz cel skip-linku tuż przed główną treścią strony
    (function() {
        if (document.getElementById('pjm-content-start')) return;
        var main = document.querySelector('main, #main, #content, .site-main, article, .elementor-section');
        if (main && main.parentNode) {
            var anchor = document.createElement('span');
            anchor.id = 'pjm-content-start';
            anchor.setAttribute('tabindex', '-1');
            main.parentNode.insertBefore(anchor, main);
        }
    })();

    var header = document.getElementById('pjm-site-header');
    var nav = document.getElementById('pjm-header-nav');
    var hamburger = document.getElementById('pjm-hamburger');

    if (!header) return;

    // Scroll effect
    var lastScroll = 0;
    window.addEventListener('scroll', function() {
        var y = window.scrollY || window.pageYOffset;
        header.classList.toggle('scrolled', y > 20);
        lastScroll = y;
    }, { passive: true });

    // Hamburger toggle
    if (hamburger && nav) {
        hamburger.addEventListener('click', function() {
            var open = nav.classList.toggle('open');
            hamburger.classList.toggle('active', open);
            hamburger.setAttribute('aria-expanded', open);
            document.body.style.overflow = open ? 'hidden' : '';
        });
    }

    // Dropdown toggle on mobile
    var triggers = header.querySelectorAll('.pjm-dropdown-trigger');
    triggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            if (window.innerWidth <= 960) {
                e.preventDefault();
                var parent = trigger.closest('.pjm-has-dropdown');
                parent.classList.toggle('open');
            }
        });
    });

    // Close mobile menu on link click
    var links = nav ? nav.querySelectorAll('.pjm-nav-link:not(.pjm-dropdown-trigger), .pjm-dropdown a') : [];
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            if (nav.classList.contains('open')) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });
    });
})();
