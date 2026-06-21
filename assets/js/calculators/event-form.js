jQuery(document).ready(function ($) {

    // --- 1. KONFIGURACJA I STAN ---
    if ($('.pjm-event-form').length === 0) return;

    const configEl = document.getElementById('pjm-event-tiers-data');
    if (!configEl) return;

    let tiersData = [];
    try { tiersData = JSON.parse(configEl.dataset.tiers || '[]'); } catch (e) { }
    const travelRate = parseFloat(configEl.dataset.travelRate || 7);

    let state = {
        mode: 'live',
        days: [],
        distance: 0,
        serviceCost: 0,
        travelCost: 0,
        addonsCost: 0,
        totalPrice: 0,
        totalHours: 0,
        maxTranslators: 1,
        addons: []
    };

    // --- 2. LISTENERS ---

    // A. Kliknięcie w rząd (Toggle Checkbox)
    $('.pjm-event-form').on('click', '.pjm-addon-row', function (e) {
        // Ignoruj kliknięcia w interaktywne elementy
        if ($(e.target).is('input, button, label, .plus, .minus')) return;

        const checkbox = $(this).find('.pjm-addon-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // B. Obsługa liczników (+/-) z blokadą propagacji
    $('.pjm-event-form').on('click', '.plus, .minus', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Kluczowe: nie uruchamiaj kliknięcia w rząd

        const row = $(this).closest('.pjm-addon-row');
        const input = row.find('.pjm-addon-qty-input');
        const checkbox = row.find('.pjm-addon-checkbox');
        let val = parseInt(input.val()) || 0;

        if ($(this).hasClass('plus')) {
            val++;
        } else if (val > 0) {
            val--;
        }

        input.val(val);
        // Automatycznie zaznacz jeśli > 0, odznacz jeśli 0
        checkbox.prop('checked', val > 0);

        // Zarządzanie stanem przycisku minus
        $(this).parent().find('.minus').prop('disabled', val === 0);

        recalculate();
    });

    // C. Zmiana Trybu (Live/Online)
    $('.pjm-event-form').off('change', '.pjm-mode-radio').on('change', '.pjm-mode-radio', function () {
        state.mode = $(this).val();
        $('.jm-card-ot').removeClass('featured');
        $(this).closest('.jm-card-ot').addClass('featured');

        if (state.mode === 'online') {
            $('#pjm-location-section').slideUp();
            state.distance = 0;
            $('#event-distance').val(0);
        } else {
            $('#pjm-location-section').slideDown();
        }
        recalculate();
    });

    // D. Dystans
    $('#event-distance').on('input', function () {
        state.distance = parseFloat($(this).val()) || 0;
        recalculate();
    });

    // E. Zarządzanie Dniami
    $('#btn-add-event-day').off('click').on('click', addDayRow);

    $('#event-days-container').on('click', '.btn-remove-day', function () {
        $(this).closest('.pjm-day-row').remove();
        recalculate();
    });

    // F. Przeliczanie przy zmianach
    $('#event-days-container').on('input change', 'input', recalculate);
    $('.pjm-event-form').on('change', '.pjm-addon-checkbox', recalculate);

    // G. Dodaj do koszyka
    $('#btn-add-event').off('click').on('click', addToCart);

    // Init: Dodaj pierwszy dzień jeśli brak
    if ($('#event-days-container').children().length === 0) addDayRow();

    // --- 3. LOGIKA OBLICZEŃ ---

    function addDayRow() {
        const id = Date.now();
        const today = new Date().toISOString().split('T')[0];
        const html = `
            <div class="pjm-day-row" data-id="${id}" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:10px; background:#fff; padding:15px; border:1px solid #eee; border-radius:8px;">
                <div class="jm-input-group" style="flex:1;">
                    <label for="day-date-${id}">Data</label>
                    <input type="date" id="day-date-${id}" class="pjm-input day-date" value="${today}" aria-label="Data wydarzenia" required>
                </div>
                <div class="jm-input-group" style="width:100px;">
                    <label for="day-start-${id}">Start</label>
                    <input type="time" id="day-start-${id}" class="pjm-input day-start" value="09:00" aria-label="Godzina rozpoczęcia">
                </div>
                <div class="jm-input-group" style="width:100px;">
                    <label for="day-end-${id}">Koniec</label>
                    <input type="time" id="day-end-${id}" class="pjm-input day-end" value="12:00" aria-label="Godzina zakończenia">
                </div>
                <button type="button" class="btn-remove-day" style="background:#ffebee; color:#c62828; border:none; width:40px; height:40px; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <span class="material-symbols-rounded">delete</span>
                </button>
            </div>`;
        $('#event-days-container').append(html);
        recalculate();
    }

    function recalculate() {
        // Reset stanu
        state.days = [];
        state.addons = [];
        state.serviceCost = 0;
        state.totalHours = 0;
        state.maxTranslators = 1;
        state.addonsCost = 0;

        // 1. Dni i godziny — SUMUJEMY godziny ze wszystkich dni i wybieramy JEDEN próg.
        //    To jest dokładnie ta sama formuła co serwer (calculate_service_cost, case 'event'):
        //    total_hours -> próg po ceil(total_hours) -> koszt = total_hours * stawka * tłumacze.
        //    Wcześniej JS liczył per-dzień, więc cena w kalkulatorze ≠ cena naliczana przy „Dodaj do koszyka".
        state.hourlyRate = 0;
        $('.pjm-day-row').each(function () {
            const date = $(this).find('.day-date').val();
            const start = $(this).find('.day-start').val();
            const end = $(this).find('.day-end').val();

            if (date && start && end) {
                let d1 = new Date(`2000-01-01T${start}`);
                let d2 = new Date(`2000-01-01T${end}`);
                let diff = (d2 - d1) / 36e5; // różnica w godzinach
                if (diff < 0) diff += 24;
                if (diff < 1) diff = 1;

                state.totalHours += diff;
                state.days.push({ date, start, end, duration: diff });
            }
        });

        if (state.totalHours > 0) {
            tiersData.sort((a, b) => a.max_hours - b.max_hours);
            // Próg po SUMIE godzin zaokrąglonej w górę (ceil) — spójnie z serwerem.
            let hCeil = Math.ceil(state.totalHours);
            let tier = tiersData.find(t => hCeil >= t.min_hours && hCeil <= t.max_hours) || tiersData[tiersData.length - 1];

            if (tier) {
                let rate = parseFloat(tier.rate);
                let trans = parseInt(tier.translators || 1);
                if (state.mode === 'online' && tier.online_disc > 0) rate *= (1 - (tier.online_disc / 100));

                state.serviceCost = state.totalHours * rate * trans;
                state.maxTranslators = trans;
                state.hourlyRate = rate;
            }
        }

        // 2. Koszt Dojazdu
        if (state.mode === 'live' && state.distance > 0) {
            const cars = Math.ceil(state.maxTranslators / 2);
            state.travelCost = state.distance * 2 * travelRate * cars;
        } else {
            state.travelCost = 0;
        }

        // 3. Dodatki (z obsługą mnożnika dni)
        const daysCount = state.days.length || 1;

        $('.pjm-event-form .pjm-addon-checkbox:checked').each(function () {
            const el = $(this);
            const price = parseFloat(el.data('price'));
            const mode = el.data('mode');
            const qtyInput = el.closest('.pjm-addon-row').find('.pjm-addon-qty-input');
            const qty = qtyInput.length ? (parseInt(qtyInput.val()) || 1) : 1;

            let itemCost = 0;
            if (mode === 'hourly') {
                itemCost = price * state.totalHours;
            } else if (mode === 'per_unit') {
                // Mnożymy: Cena * Liczba Dni * Liczba Sztuk (np. pętli)
                itemCost = price * daysCount * qty;
            } else {
                itemCost = price; // Flat fee
            }

            state.addonsCost += itemCost;

            // Budowanie nazwy dla koszyka
            let nameSuffix = '';
            if (mode === 'per_unit' && daysCount > 1) {
                nameSuffix = ` (${daysCount} dni x ${qty} szt.)`;
            } else if (qty > 1) {
                nameSuffix = ` (x${qty})`;
            }

            state.addons.push({
                code: el.val(),
                name: el.data('name') + nameSuffix,
                price: itemCost
            });
        });

        state.totalPrice = state.serviceCost + state.travelCost + state.addonsCost;

        // UI Updates
        $('#event-summary-empty').toggle(state.days.length === 0);
        $('#event-summary-content').toggle(state.days.length > 0);

        $('#event-sidebar-service-cost').text(fmt(state.serviceCost));
        $('#event-price-display').text(fmt(state.totalPrice));

        $('#event-summary-time').text(state.totalHours.toFixed(1) + 'h');
        $('#event-summary-days').text(state.days.length + (state.days.length === 1 ? ' dzień' : ' dni'));
        $('#event-summary-translators').text(state.maxTranslators + ' os.');

        $('#event-hourly-rate').text(fmt(state.hourlyRate || 0) + '/h');

        // Renderowanie listy dodatków w sidebarze
        let addonsHtml = '';
        state.addons.forEach(ad => {
            addonsHtml += `<div class="summary-row small"><span>${ad.name}</span><span>${fmt(ad.price)}</span></div>`;
        });
        if (state.travelCost > 0) {
            addonsHtml += `<div class="summary-row small"><span>Dojazd (${state.distance} km)</span><span>${fmt(state.travelCost)}</span></div>`;
        }
        $('#event-addons-list').html(addonsHtml);
    }

    function fmt(n) { return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' zł'; }
    function generateUUID() { return 'xxxx-xxxx'.replace(/[x]/g, c => (Math.random() * 16 | 0).toString(16)); }

    // --- 4. DODAWANIE DO KOSZYKA ---

    function addToCart() {
        if (state.totalPrice <= 0) return;

        if (typeof PJM_Core === 'undefined' || typeof PJM_Core.processAddToCart !== 'function') {
            alert('Błąd systemu: PJM_Core is missing');
            return;
        }

        const title = $('#event-title').val() || 'Tłumaczenie wydarzenia';
        const dateStrings = state.days.map(d => `${d.date} (${d.start}-${d.end})`).join(', ');

        // Przygotowanie dodatków do zapisu
        const finalAddons = state.addons.map(ad => ({
            code: ad.code,
            name: ad.name,
            price: ad.price
        }));

        if (state.travelCost > 0) {
            finalAddons.push({
                code: 'travel',
                name: `Dojazd i logistyka (${state.distance} km)`,
                price: state.travelCost
            });
        }

        const cartItem = {
            id: generateUUID(),
            service_type: 'event',
            title: title,
            quantity: 1,
            unit: 'usługa',
            pricing: { total: parseFloat(state.totalPrice.toFixed(2)) },
            delivery: { mode: 'standard' }, // Eventy są zawsze standard
            meta: {
                mode: state.mode,
                scope: `${state.totalHours.toFixed(1)}h (${state.maxTranslators} os.)`,
                dates: dateStrings,
                location: state.mode === 'live' ? $('#event-city').val() : 'Online',
                notes: $('#event-notes').val(),
                addons_list: finalAddons.map(a => a.name).join(', ')
            },
            files: { source_url: "", delivery_url: "" },
            _addons_data: finalAddons // Ważne dla wyświetlania szczegółów w koszyku
        };

        const fd = new FormData();
        fd.append('action', 'pjm_add_cart_item');
        if (typeof pjm_calc_vars !== 'undefined') fd.append('nonce', pjm_calc_vars.nonce);
        fd.append('cart_item_json', JSON.stringify(cartItem));

        PJM_Core.processAddToCart(fd, 'event');
    }
});