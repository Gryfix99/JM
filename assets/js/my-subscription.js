/**
 * Obsługa zakładki "Mój Abonament" (Panel Klienta)
 * Lokalizacja: assets/js/my-subscription.js
 * Wersja: 4.0 (Pricing Toggle + AJAX Cancellation)
 */
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('jm-billing-input');
    const txtMonthly = document.getElementById('txt-monthly');
    const txtYearly = document.getElementById('txt-yearly');
    const cards = document.querySelectorAll('.jm-p-card');
    const links = document.querySelectorAll('.pjm-checkout-link');
    const cancelBtn = document.getElementById('pjm-cancel-sub-trigger');

    // Formater waluty
    const formatter = new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    // ==========================================
    // 1. LOGIKA PRZEŁĄCZANIA CEN
    // ==========================================
    function updatePricing() {
        if (!toggle) return;

        const isYearly = toggle.checked;
        const cycleParam = isYearly ? 'yearly' : 'monthly';

        // Aktualizacja etykiet tekstowych
        if (txtMonthly && txtYearly) {
            txtMonthly.classList.toggle('active', !isYearly);
            txtYearly.classList.toggle('active', isYearly);
        }

        // Aktualizacja cen na kartach
        cards.forEach(card => {
            const priceEl = card.querySelector('.jm-price-num');
            const labelEl = card.querySelector('.jm-price-lbl');
            const savingEl = card.querySelector('.yearly-saving');

            const priceMonthly = parseFloat(card.dataset.priceMonthly);
            const priceYearlyAvg = parseFloat(card.dataset.priceYearly);

            if (isNaN(priceMonthly) || priceMonthly <= 0) return;

            if (priceEl) {
                if (isYearly) {
                    priceEl.innerText = formatter.format(priceYearlyAvg);
                    if (labelEl) labelEl.innerText = 'PLN / MSC (PRZY WPŁACIE ZA ROK)';
                    if (savingEl) savingEl.style.display = 'inline-block';
                } else {
                    priceEl.innerText = formatter.format(priceMonthly);
                    if (labelEl) labelEl.innerText = 'BRUTTO / MSC';
                    if (savingEl) savingEl.style.display = 'none';
                }
            }
        });

        // Aktualizacja parametrów w linkach zakupowych (funkcja zakupowa)
        links.forEach(link => {
            try {
                let currentUrl = new URL(link.getAttribute('href'), window.location.origin);
                currentUrl.searchParams.set('cycle', cycleParam);
                link.href = currentUrl.toString();
            } catch (e) {
                console.warn('Błąd aktualizacji linku:', e);
            }
        });
    }

    if (toggle) {
        toggle.addEventListener('change', updatePricing);
        // Inicjalizacja przy starcie
        updatePricing();
    }

    // ==========================================
    // 2. OBSŁUGA ANULOWANIA SUBSKRYPCJI (AJAX)
    // ==========================================
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const deadline = this.dataset.deadline || 'końca kontraktu';
            const confirmMsg = `Czy na pewno chcesz anulować przedłużenie subskrypcji?\n\n` +
                `Twoje zobowiązanie 12-miesięczne pozostaje aktywne do: ${deadline}.\n` +
                `Do tego czasu środki będą pobierane co miesiąc, a Gesty doliczane. ` +
                `Po tej dacie subskrypcja wygaśnie definitywnie.`;

            if (confirm(confirmMsg)) {
                // Blokujemy przycisk na czas wysyłki
                cancelBtn.disabled = true;
                const originalText = cancelBtn.innerText;
                cancelBtn.innerText = 'Przetwarzanie...';

                // Wywołanie AJAX (wymaga jQuery, które jest standardem w WP)
                if (typeof jQuery !== 'undefined') {
                    jQuery.ajax({
                        url: PJM_VARS.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'pjm_cancel_subscription',
                            nonce: PJM_VARS.nonce
                        },
                        success: function (response) {
                            if (response.success) {
                                alert(response.data.message);
                                window.location.reload();
                            } else {
                                alert('Błąd: ' + (response.data.message || 'Nieznany błąd serwera.'));
                                cancelBtn.disabled = false;
                                cancelBtn.innerText = originalText;
                            }
                        },
                        error: function () {
                            alert('Błąd połączenia z serwerem. Spróbuj ponownie później.');
                            cancelBtn.disabled = false;
                            cancelBtn.innerText = originalText;
                        }
                    });
                } else {
                    console.error('Błąd: jQuery nie jest załadowane.');
                    alert('Wystąpił błąd techniczny (brak jQuery). Skontaktuj się z administracją.');
                }
            }
        });
    }
});