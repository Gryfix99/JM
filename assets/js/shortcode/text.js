document.addEventListener('DOMContentLoaded', function () {

    /* =========================
       1. TABS
    ========================== */
    const tabTriggers = document.querySelectorAll('.jm-tab-trigger');
    const tabContents = document.querySelectorAll('.jm-tab-content');

    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function () {
            tabTriggers.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            const target = document.getElementById(this.dataset.target);
            if (target) target.classList.add('active');
        });
    });

    /* =========================
       2. KALKULATOR
    ========================== */

    const input = document.getElementById('text-calc-input');
    const textarea = document.getElementById('text-calc-content');

    if (!input) return;

    const priceDisplay = document.getElementById('text-price-display');
    const tierDisplay = document.getElementById('text-tier-name');
    const checkboxes = document.querySelectorAll('.jm-calc-addon-cb');

    const packagesConfig = (typeof pjmTextConfig !== 'undefined') ? pjmTextConfig : [];

    function calculate() {

        let chars = 0;

        // Jeśli wklejono tekst → liczymy znaki
        if (textarea && textarea.value.trim().length > 0) {
            chars = textarea.value.length;
            input.value = chars;
        } else {
            chars = parseInt(input.value) || 0;
        }

        if (chars < 1) chars = 1;

        // 1 strona = 1800 zzs
        let pages = Math.ceil(chars / 1800);

        let basePrice = 0;
        let selectedTierName = "—";

        /* =========================
           WYBÓR TIERU
        ========================== */

        for (let i = 0; i < packagesConfig.length; i++) {

            let pkg = packagesConfig[i];
            let limit = parseInt(pkg.limit);
            let base = parseFloat(pkg.base);
            let rate = parseFloat(pkg.per_unit);

            if (pages <= limit) {

                selectedTierName = pkg.name;

                // Algorytm jak video
                let previousLimit = 0;
                if (i > 0) {
                    previousLimit = parseInt(packagesConfig[i - 1].limit);
                }

                let unitsInTier = pages - previousLimit;
                basePrice = base + (unitsInTier * rate);

                break;
            }
        }

        /* =========================
           DODATKI
        ========================== */

        let addonsTotal = 0;

        checkboxes.forEach(cb => {
            if (cb.checked) {

                let price = parseFloat(cb.dataset.price);
                let mode = cb.dataset.mode;

                if (mode === 'per_unit') {
                    addonsTotal += price * pages;
                } else {
                    addonsTotal += price;
                }
            }
        });

        let finalPrice = basePrice + addonsTotal;

        /* =========================
           UPDATE UI
        ========================== */

        if (priceDisplay) {
            priceDisplay.innerText = finalPrice.toLocaleString('pl-PL') + " zł";
        }

        if (tierDisplay) {
            tierDisplay.innerText = selectedTierName + " (" + pages + " str.)";
        }
    }

    /* =========================
       EVENT LISTENERS
    ========================== */

    input.addEventListener('input', calculate);
    input.addEventListener('change', calculate);

    if (textarea) {
        textarea.addEventListener('input', calculate);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', calculate);
    });

    calculate();
});


/* =========================
   SELECT PACKAGE (GLOBAL)
========================== */

function selectTextPackage(pages) {

    const calcSection = document.getElementById('text-kalkulator');
    const input = document.getElementById('text-calc-input');

    if (calcSection && input) {

        calcSection.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        input.value = pages * 1800;
        input.dispatchEvent(new Event('input'));
    }
}
