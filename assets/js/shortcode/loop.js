document.addEventListener('DOMContentLoaded', function () {

    // --- 1. TABS LOGIC ---
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

    // --- 2. KALKULATOR ---
    if (typeof pjmLoopPackages === 'undefined') return;

    const rangeInput = document.getElementById('loop-range-days'); // Teraz już istnieje
    const numberInput = document.getElementById('loop-input-days');
    const displayPrice = document.getElementById('loop-price-display');
    const displayBadge = document.getElementById('loop-tier-badge');
    const addonCheckboxes = document.querySelectorAll('.jm-loop-addon-cb');
    const orderBtn = document.getElementById('btn-loop-order');

    if (!numberInput) return;

    function calculateLoop() {
        let days = parseInt(numberInput.value) || 1;
        if (days < 1) days = 1;

        // Znajdź odpowiedni pakiet cenowy
        let activePkg = pjmLoopPackages.find(pkg => {
            let min = parseInt(pkg.unit_range[0]);
            let max = parseInt(pkg.unit_range[1]) || 9999;
            return days >= min && days <= max;
        }) || pjmLoopPackages[pjmLoopPackages.length - 1];

        // LOGIKA CENOWA
        const baseCostStart = parseFloat(activePkg.config.tier_base_cost || 0);
        const ratePerDay = parseFloat(activePkg.config.rate_per_unit || 0);
        const minDaysInTier = parseInt(activePkg.unit_range[0]);

        // LICZENIE: Baza + (Dni powyżej progu * Stawka)
        let daysOverThreshold = days - (minDaysInTier - 1);
        let totalBase = baseCostStart + (daysOverThreshold * ratePerDay);

        // DODATKI
        let addonsCost = 0;
        const selectedAddons = [];
        addonCheckboxes.forEach(cb => {
            if (cb.checked) {
                let price = parseFloat(cb.dataset.price);
                addonsCost += (cb.dataset.mode === 'per_unit') ? (price * days) : price;
                selectedAddons.push(cb.dataset.id);
            }
        });

        let finalPrice = totalBase + addonsCost;

        // UI UPDATE
        if (displayPrice) displayPrice.textContent = finalPrice.toLocaleString('pl-PL') + ' zł';
        if (displayBadge) displayBadge.textContent = activePkg.name;

        if (orderBtn) {
            orderBtn.dataset.days = days;
            orderBtn.dataset.addons = selectedAddons.join(',');
        }
    }

    // Synchronizacja inputów
    if (rangeInput && numberInput) {
        rangeInput.addEventListener('input', function () {
            numberInput.value = this.value;
            calculateLoop();
        });
        numberInput.addEventListener('input', function () {
            let val = parseInt(this.value) || 1;
            rangeInput.value = (val <= 30) ? val : 30;
            calculateLoop();
        });
    }

    addonCheckboxes.forEach(cb => cb.addEventListener('change', calculateLoop));
    calculateLoop();

    // Obsługa zamówienia
    if (orderBtn) {
        orderBtn.addEventListener('click', function () {
            const days = this.dataset.days;
            const addons = this.dataset.addons;
            let url = `/moje-konto/?tab=calculator&service=loop&qty=${days}`;
            if (addons) url += `&addons=${addons}`;
            window.location.href = url;
        });
    }
});

// Funkcja globalna dla przycisków "Wybierz"
window.selectLoopDays = function (days) {
    const numInput = document.getElementById('loop-input-days');
    if (numInput) {
        numInput.value = days;
        numInput.dispatchEvent(new Event('input'));
        document.getElementById('loop-kalkulator').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};