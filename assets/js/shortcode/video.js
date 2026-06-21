document.addEventListener('DOMContentLoaded', function () {

    // --- 1. OBSŁUGA ZAKŁADEK (TABS) ---
    const tabTriggers = document.querySelectorAll('.jm-tab-trigger');
    const tabContents = document.querySelectorAll('.jm-tab-content');

    if (tabTriggers.length > 0) {
        tabTriggers.forEach(trigger => {
            trigger.addEventListener('click', function () {
                // Reset klas aktywnych
                tabTriggers.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Aktywacja klikniętego
                this.classList.add('active');
                const targetId = this.dataset.target;
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    // --- 2. LOGIKA KALKULATORA WIDEO ---
    const input = document.getElementById('video-calc-input');

    // Jeśli kalkulatora nie ma na stronie, przerywamy działanie skryptu
    if (!input) return;

    const priceDisplay = document.getElementById('video-price-display');
    const tierDisplay = document.getElementById('video-tier-name');
    const checkboxes = document.querySelectorAll('.jm-calc-addon-cb');

    // Pobieramy konfigurację z PHP (zmienna globalna) lub fallback
    const packagesConfig = (typeof pjmVideoConfig !== 'undefined') ? pjmVideoConfig : [];

    function calculate() {
        let actualMins = parseInt(input.value) || 0;

        // Zabezpieczenie: min 1 minuta
        if (actualMins < 1) actualMins = 1;

        // Limit dla standardowych pakietów do 120 minut
        let mins = actualMins > 120 ? 120 : actualMins;

        let basePrice = 0;
        let selectedTierName = "Niestandardowy";
        let previousLimit = 0; 

        // A. Znajdowanie odpowiedniego pakietu (Tier) dla minut <= 120
        for (let i = 0; i < packagesConfig.length; i++) {
            let pkg = packagesConfig[i];
            let limit = parseInt(pkg.limit);

            // Jeśli minuty mieszczą się w limicie tego pakietu
            if (mins <= limit) {
                selectedTierName = pkg.name;

                let ratePerMinute = parseFloat(pkg.per_minute) || 0;
                let baseCost = parseFloat(pkg.base) || 0;

                if (ratePerMinute > 0) {
                    let extraMinutes = Math.max(0, mins - previousLimit);
                    basePrice = baseCost + (extraMinutes * ratePerMinute);
                } else {
                    // Ryczałt (stała cena za cały przedział)
                    basePrice = baseCost;
                }
                break;
            }

            // Ustawiamy limit tego pakietu jako "poprzedni" dla następnej iteracji
            previousLimit = limit;
        }

        // Doliczenie kwoty za minuty powyżej 120 (30 zł za każdą kolejną minutę)
        if (actualMins > 120) {
            let extraMinutesOver120 = actualMins - 120;
            basePrice += extraMinutesOver120 * 30;
            selectedTierName = "Niestandardowy (>120 min)";
        }

        // B. Doliczanie dodatków (dla pełnej ilości minut)
        let addonsTotal = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                let price = parseFloat(cb.dataset.price) || 0;
                let mode = cb.dataset.mode; // 'per_unit' lub 'flat'

                if (mode === 'per_unit') {
                    addonsTotal += price * actualMins;
                } else {
                    addonsTotal += price;
                }
            }
        });

        let finalPrice = basePrice + addonsTotal;

        // C. Aktualizacja UI
        if (priceDisplay) {
            priceDisplay.innerText = finalPrice.toLocaleString('pl-PL') + " zł";
        }
        if (tierDisplay) {
            tierDisplay.innerText = "Pakiet: " + selectedTierName;
        }
    }

    // Nasłuchiwanie zmian
    input.addEventListener('input', calculate);
    input.addEventListener('change', calculate); // Dla pewności przy strzałkach
    checkboxes.forEach(cb => cb.addEventListener('change', calculate));

    // Inicjalizacja przy starcie
    calculate();
});

// --- 3. FUNKCJA POMOCNICZA (Globalna) ---
// Wywoływana przez przyciski "Wybierz" w sekcji pakietów
function selectVideoPackage(minutes) {
    const calcSection = document.getElementById('video-kalkulator');
    const input = document.getElementById('video-calc-input');

    if (calcSection && input) {
        // Przewiń do kalkulatora
        calcSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Ustaw wartość
        input.value = minutes;

        // Wymuś zdarzenie input, aby przeliczyć cenę natychmiast
        input.dispatchEvent(new Event('input'));
    }
}