/**
 * Obsługa cennika - Shortcode (Subscription Toggle)
 * Lokalizacja: assets/js/subscription.js
 * Wersja: 3.0 (Unified Logic & Modal)
 */
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('jm-billing-input');
    const txtMonthly = document.getElementById('txt-monthly');
    const txtYearly = document.getElementById('txt-yearly');

    // Stała: Płatność roczna to równowartość 11 miesięcy
    const yearlyMonthsPay = 11;

    // Formater walutowy
    const formatter = new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    // Definiujemy funkcję globalnie (dla PHP triggera)
    window.pjmUpdatePricing = function () {
        // Jeśli nie ma toggle na stronie, nie rób nic (bezpiecznik)
        if (!toggle) return;

        const isYearly = toggle.checked;
        const cycleParam = isYearly ? 'yearly' : 'monthly';

        // 1. Stylizacja etykiet
        if (txtMonthly && txtYearly) {
            if (isYearly) {
                txtMonthly.style.color = '#ccc';
                txtMonthly.style.fontWeight = '400';

                txtYearly.style.color = '#f39c12';
                txtYearly.style.fontWeight = '700';
            } else {
                txtMonthly.style.color = '#2c3e50';
                txtMonthly.style.fontWeight = '700';

                txtYearly.style.color = '#ccc';
                txtYearly.style.fontWeight = '400';
            }
        }

        // 2. Aktualizacja cen
        // Pobieramy elementy ceny z data-base (PHP Shortcode)
        const priceElements = document.querySelectorAll('.jm-price-num[data-base]');

        priceElements.forEach(el => {
            const basePrice = parseFloat(el.dataset.base);
            const card = el.closest('.jm-p-card'); // Pobieramy kartę nadrzędną
            const labelEl = card ? card.querySelector('.jm-price-lbl') : null;
            const subText = card ? card.querySelector('.jm-price-subtext') : null;

            if (isNaN(basePrice) || basePrice <= 0) return;

            // Animacja
            el.style.transition = 'opacity 0.2s ease';
            el.style.opacity = '0.5';

            setTimeout(() => {
                if (isYearly) {
                    // OBLICZANIE: (Cena * 11) / 12 = Średnia
                    const yearlyTotal = Math.floor(basePrice * yearlyMonthsPay);
                    const yearlyAvg = Math.floor(yearlyTotal / 12);

                    el.innerText = formatter.format(yearlyAvg);

                    if (labelEl) labelEl.innerText = 'PLN / MSC (PRZY WPŁACIE ZA ROK)';
                    if (subText) {
                        subText.style.display = 'block';
                        subText.innerText = 'Rocznie: ' + formatter.format(yearlyTotal);
                    }
                } else {
                    // STANDARD
                    const monthlyPrice = Math.floor(basePrice);

                    el.innerText = formatter.format(monthlyPrice);

                    if (labelEl) labelEl.innerText = 'NETTO / MSC';
                    if (subText) subText.style.display = 'none';
                }
                el.style.opacity = '1';
            }, 150);
        });

        // 3. Aktualizacja linków
        const links = document.querySelectorAll('.pjm-checkout-link');
        links.forEach(link => {
            try {
                let url = new URL(link.getAttribute('href'), window.location.origin);
                url.searchParams.set('cycle', cycleParam);
                link.href = url.toString();
            } catch (e) { }
        });
    };

    // Nasłuchiwanie
    if (toggle) {
        toggle.addEventListener('change', window.pjmUpdatePricing);
        window.pjmUpdatePricing(); // Init startowy
    }
});

// --- LOGIKA MODALA (Dla Premium) ---
function openPjmModal(planName) {
    const modal = document.getElementById('jm-modal-overlay');
    const title = document.getElementById('jm-selected-package');
    if (modal) {
        modal.style.display = 'flex';
        if (title) title.innerText = planName;
    }
}

function closePjmModal() {
    const modal = document.getElementById('jm-modal-overlay');
    if (modal) modal.style.display = 'none';
}

// Zamknij modal po kliknięciu w tło
window.onclick = function (event) {
    const modal = document.getElementById('jm-modal-overlay');
    if (event.target === modal) {
        modal.style.display = "none";
    }
}