document.addEventListener('DOMContentLoaded', function () {

    // --- 1. OBSŁUGA ZAKŁADEK (TABS) ---
    const tabTriggers = document.querySelectorAll('.jm-tab-trigger');
    const tabContents = document.querySelectorAll('.jm-tab-content');

    if (tabTriggers.length > 0) {
        tabTriggers.forEach(trigger => {
            trigger.addEventListener('click', function () {
                // Reset
                tabTriggers.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Aktywacja
                this.classList.add('active');
                const targetId = this.dataset.target;
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    // --- 2. LOGIKA KALKULATORA ---

    // Sprawdzenie czy dane zostały przekazane z PHP
    if (typeof pjmEventPackages === 'undefined') return;

    // Pobranie elementów DOM związanych z nową strukturą HTML (z poprzedniego pliku PHP)
    const eventDaysContainer = document.getElementById('event-days-container');
    const displayPrice = document.getElementById('event-price-display');
    const displayTier = document.getElementById('event-tier-name');
    const displayRate = document.getElementById('event-hourly-rate');
    const addonCheckboxes = document.querySelectorAll('.jm-calc-addon-cb');
    const modeRadios = document.querySelectorAll('input[name="event_mode"]');
    const btnAddDay = document.getElementById('btn-add-event-day');

    // Elementy zaktualizowanego UI (z podsumowaniem JS)
    const summaryTime = document.getElementById('event-summary-time');
    const summaryDays = document.getElementById('event-summary-days');
    const summaryTranslators = document.getElementById('event-summary-translators');

    // Zamiast przerywać przy braku inputHours, przerywamy przy braku kontenera na dni
    if (!eventDaysContainer) return;

    // Globalne zmienne konfiguracyjne z PHP
    let rateFirstHour = (typeof pjmBaseRate1h !== 'undefined') ? parseFloat(pjmBaseRate1h) : 200;
    let threshold = pjmGlobalRules && pjmGlobalRules.translators_threshold ? parseInt(pjmGlobalRules.translators_threshold) : 1;
    let multiplier = pjmGlobalRules && pjmGlobalRules.translators_multiplier ? parseInt(pjmGlobalRules.translators_multiplier) : 1;

    function addDayRow() {
        const id = Date.now();
        const today = new Date().toISOString().split('T')[0];
        
        const rowHTML = `
            <div class="pjm-day-row" data-id="${id}" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:10px; background:#fff; padding:15px; border:1px solid #eee; border-radius:8px;">
                <div class="jm-input-group" style="flex:1;">
                    <label>Data</label>
                    <input type="date" class="pjm-input day-date" value="${today}" required>
                </div>
                <div class="jm-input-group" style="width:100px;">
                    <label>Start</label>
                    <input type="time" class="pjm-input day-start" value="09:00">
                </div>
                <div class="jm-input-group" style="width:100px;">
                    <label>Koniec</label>
                    <input type="time" class="pjm-input day-end" value="12:00">
                </div>
                <button type="button" class="btn-remove-day" style="background:#ffebee; color:#c62828; border:none; width:40px; height:40px; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <span class="material-symbols-rounded">delete</span>
                </button>
            </div>
        `;
        
        eventDaysContainer.insertAdjacentHTML('beforeend', rowHTML);
        calculateTotal();
    }

    function calculateTotal() {
        let totalHours = 0;
        let dayRows = document.querySelectorAll('.pjm-day-row');
        let daysCount = dayRows.length;
        
        // Czas z każdego dnia jest obliczany, a każda rozpoczęta godzina zaokrąglana w górę
        dayRows.forEach(row => {
            const start = row.querySelector('.day-start').value;
            const end = row.querySelector('.day-end').value;

            if (start && end) {
                let d1 = new Date(`2000-01-01T${start}`);
                let d2 = new Date(`2000-01-01T${end}`);
                let diffHours = (d2 - d1) / 36e5; // różnica w godzinach
                
                if (diffHours < 0) diffHours += 24; // Przejście przez północ
                
                // Zaokrąglenie w górę każdej rozpoczętej godziny, minimum 1 godzina na blok
                diffHours = Math.ceil(diffHours);
                if (diffHours < 1) diffHours = 1;

                totalHours += diffHours;
            }
        });

        if (totalHours === 0) {
            // Reset UI jeśli brak wyliczonych godzin
            if (displayPrice) displayPrice.textContent = '0 zł';
            if (displayTier) displayTier.textContent = '—';
            if (displayRate) displayRate.innerHTML = '';
            if (summaryTime) summaryTime.textContent = '0h';
            if (summaryDays) summaryDays.textContent = '0';
            if (summaryTranslators) summaryTranslators.textContent = '0 os.';
            return;
        }

        // A. ZNAJDŹ PAKIET (wg unit_range) na podstawie zsumowanych godzin
        let activePkg = pjmEventPackages.find(pkg => {
            let min = parseInt(pkg.unit_range[0]);
            let max = parseInt(pkg.unit_range[1]) || 999;
            return totalHours >= min && totalHours <= max;
        });

        if (!activePkg) {
            activePkg = pjmEventPackages[pjmEventPackages.length - 1];
        }

        // B. POBIERZ PARAMETRY PAKIETU
        let rateNextHour = parseFloat(activePkg.config.rate_per_translator || 0);
        let onlineDiscountPercent = parseFloat(activePkg.config.online_discount || 0);

        // C. OBLICZ KOSZT BAZOWY (DLA 1 TŁUMACZA)
        let baseCost = 0;
        if (totalHours === 1) {
            baseCost = rateFirstHour;
        } else {
            // Pierwsza godzina (np. droższa) + (pozostałe godziny * stawka kolejna z pakietu)
            // Logika sumuje całkowity czas wydarzenia.
            baseCost = rateFirstHour + ((totalHours - 1) * rateNextHour);
        }

        // D. ZESPÓŁ TŁUMACZY (MNOŻNIK)
        // Zakładamy, że sprawdzamy limit godzin w stosunku do najdłuższego pojedynczego bloku
        // Można to też liczyć na sumę godzin - zgodnie z pierwotnym skryptem:
        let translatorsNeeded = 1;
        if (totalHours > threshold) {
            baseCost *= multiplier;
            translatorsNeeded = multiplier;
        }

        // E. RABAT ONLINE (Procentowy)
        const checkedMode = document.querySelector('input[name="event_mode"]:checked');
        const isOnline = checkedMode && checkedMode.value === 'online';
        
        if (isOnline && onlineDiscountPercent > 0) {
            baseCost = baseCost * (1 - (onlineDiscountPercent / 100));
        }

        // F. DODATKI
        let addonsCost = 0;
        addonCheckboxes.forEach(cb => {
            if (cb.checked) {
                let price = parseFloat(cb.dataset.price);
                let mode = cb.dataset.mode;

                if (mode === 'hourly') {
                    // Często stawka za sprzęt liczona za godzinę
                    addonsCost += price * totalHours;
                } else if (mode === 'per_unit') {
                    // Mnożnik za dzień lub stała opłata - tu zakładamy opłatę dzienną w trybie per_unit dla eventów
                    addonsCost += price * daysCount;
                } else {
                    addonsCost += price;
                }
            }
        });

        let finalPrice = baseCost + addonsCost;

        // G. AKTUALIZACJA UI
        if (displayPrice) displayPrice.textContent = Math.round(finalPrice).toLocaleString('pl-PL') + ' zł';
        if (displayTier) displayTier.textContent = activePkg.name;

        if (summaryTime) summaryTime.textContent = totalHours + 'h';
        if (summaryDays) summaryDays.textContent = daysCount;
        if (summaryTranslators) summaryTranslators.textContent = translatorsNeeded + ' os.';

        // Info o stawce/rabacie
        if (displayRate) {
            if (isOnline && onlineDiscountPercent > 0) {
                displayRate.innerHTML = `<span style="color:#27ae60;">Aktywny rabat Online: -${onlineDiscountPercent}%</span>`;
            } else if (totalHours > threshold) {
                displayRate.innerHTML = `<span style="color:#e67e22;">Wycena obejmuje zespół ${translatorsNeeded} tłumaczy.</span>`;
            } else {
                displayRate.textContent = '';
            }
        }
    }

    // Listenery

    // Delegacja zdarzeń dla dynamicznie tworzonych wierszy dni
    eventDaysContainer.addEventListener('input', function(e) {
        if (e.target.tagName === 'INPUT') {
            calculateTotal();
        }
    });

    eventDaysContainer.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-day')) {
            e.target.closest('.pjm-day-row').remove();
            calculateTotal();
        }
    });

    // Dodawanie nowego dnia
    if (btnAddDay) {
        btnAddDay.addEventListener('click', addDayRow);
    }

    addonCheckboxes.forEach(cb => cb.addEventListener('change', calculateTotal));
    modeRadios.forEach(radio => radio.addEventListener('change', calculateTotal));

    // Inicjalizacja na starcie: Dodaj pierwszy pusty dzień, jeśli nic nie ma
    if (eventDaysContainer.children.length === 0) {
        addDayRow();
    } else {
        calculateTotal();
    }
});

// Funkcja globalna - dla przycisków "Wybierz" w sekcji pakietów
window.selectEventPackage = function (hours) {
    const calcSection = document.getElementById('event-kalkulator');
    const eventDaysContainer = document.getElementById('event-days-container');

    if (calcSection && eventDaysContainer) {
        // Przewiń do sekcji
        calcSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Ponieważ usunęliśmy proste pole 'hours', funkcja ta wymagałaby teraz 
        // dodania wiersza z odpowiednimi godzinami od-do (np. domyślnie 09:00 do 09:00+hours).
        // Dla uproszczenia (aby kalkulator zadziałał pod przekazany czas), resetujemy i wstawiamy jeden dzień.

        eventDaysContainer.innerHTML = ''; // Wyczyść obecne dni
        
        const id = Date.now();
        const today = new Date().toISOString().split('T')[0];
        
        let endHourNum = 9 + parseInt(hours);
        if(endHourNum > 23) endHourNum = 23;
        
        let endHourStr = (endHourNum < 10 ? '0' : '') + endHourNum + ':00';

        const rowHTML = `
            <div class="pjm-day-row" data-id="${id}" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:10px; background:#fff; padding:15px; border:1px solid #eee; border-radius:8px;">
                <div class="jm-input-group" style="flex:1;">
                    <label>Data</label>
                    <input type="date" class="pjm-input day-date" value="${today}" required>
                </div>
                <div class="jm-input-group" style="width:100px;">
                    <label>Start</label>
                    <input type="time" class="pjm-input day-start" value="09:00">
                </div>
                <div class="jm-input-group" style="width:100px;">
                    <label>Koniec</label>
                    <input type="time" class="pjm-input day-end" value="${endHourStr}">
                </div>
                <button type="button" class="btn-remove-day" style="background:#ffebee; color:#c62828; border:none; width:40px; height:40px; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <span class="material-symbols-rounded">delete</span>
                </button>
            </div>
        `;
        
        eventDaysContainer.insertAdjacentHTML('beforeend', rowHTML);

        // Ręczne wywołanie eventu aby przeliczyć wartości (ponieważ usunęliśmy sztywny dispatchEvent na inpucie)
        document.dispatchEvent(new Event('DOMContentLoaded')); // Ta symulacja może nie zadziałać idealnie
        // Bezpieczniej jest wywołać wyodrębnioną funkcję kalkulacji.
        // Jednakże `calculateTotal` jest lokalne w DOMContentLoaded. 
        // Najlepiej podpiąć custom event na kalkulatorze:
        const event = new Event('input', { bubbles: true });
        eventDaysContainer.querySelector('input').dispatchEvent(event);
    }
};