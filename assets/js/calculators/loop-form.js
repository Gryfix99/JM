jQuery(document).ready(function ($) {

    // --- 1. WALIDACJA I KONFIGURACJA ---
    if ($('.pjm-loop-form').length === 0) return;

    console.log('PJM Loop Calculator Loaded v19.7 (JSON Compatible)');

    const configEl = document.getElementById('pjm-loop-tiers-data');
    if (!configEl) {
        console.error('Brak danych konfiguracyjnych #pjm-loop-tiers-data');
        return;
    }

    let tiersData = [];
    try {
        tiersData = JSON.parse(configEl.dataset.tiers || '[]');
    } catch (e) {
        console.error('Błąd parsowania JSON tiers:', e);
    }

    const travelRate = parseFloat(configEl.dataset.travelRate || 7);

    // Stan aplikacji
    const state = {
        start: null,
        end: null,
        days: 0,
        distance: 0,
        rentCost: 0,
        travelCost: 0,
        addonsCost: 0,
        totalPrice: 0,
        selectedTier: null,
        selectedAddons: []
    };

    // --- 2. LISTENERS (OBSŁUGA ZDARZEŃ) ---

    // A. Zmiana Dat i Dystansu
    $('#loop-date-start, #loop-date-end').on('change', function () {
        calculateLoop();
    });

    $('#loop-distance').on('input', function () {
        state.distance = parseFloat($(this).val()) || 0;
        calculateLoop();
    });

    // B. Zmiana Checkboxów (Główny trigger)
    $(document).on('change', '.pjm-addon-checkbox', function () {
        calculateLoop();
    });

    // C. Kliknięcie w wiersz dodatku (UX - toggle checkbox)
    $('.pjm-loop-form').on('click', '.pjm-addon-row', function (e) {
        // Ignorujemy kliknięcia w elementy interaktywne wewnątrz wiersza
        if ($(e.target).closest('.pjm-qty-wrapper').length > 0 || $(e.target).is('input')) return;

        const checkbox = $(this).find('.pjm-addon-checkbox');
        const inputType = checkbox.data('input-type');

        // Dla typu 'counter' kliknięcie w wiersz nie powinno zmieniać checkboxa (steruje nim licznik)
        // Dla zwykłego checkboxa - przełączamy
        if (inputType !== 'counter') {
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });

    // D. Obsługa Liczników (+ / -)
    $('.pjm-loop-form').on('click', '.plus', function (e) {
        e.preventDefault(); e.stopPropagation();
        const row = $(this).closest('.pjm-qty-wrapper');
        const input = row.find('.pjm-addon-qty-input');
        const checkbox = row.closest('.pjm-addon-row').find('.pjm-addon-checkbox');

        const max = parseInt(input.attr('max')) || 99;
        let val = parseInt(input.val()) || 0;

        if (val < max) {
            val++;
            input.val(val);
            // Jeśli zwiększamy z 0 na 1 -> zaznaczamy ukryty checkbox
            if (val > 0) checkbox.prop('checked', true).trigger('change');

            // Włącz przycisk minus
            row.find('.minus').prop('disabled', false);
        }
    });

    $('.pjm-loop-form').on('click', '.minus', function (e) {
        e.preventDefault(); e.stopPropagation();
        const row = $(this).closest('.pjm-qty-wrapper');
        const input = row.find('.pjm-addon-qty-input');
        const checkbox = row.closest('.pjm-addon-row').find('.pjm-addon-checkbox');

        let val = parseInt(input.val()) || 0;

        if (val > 0) {
            val--;
            input.val(val);
            // Jeśli zmniejszamy do 0 -> odznaczamy checkbox
            if (val === 0) {
                checkbox.prop('checked', false).trigger('change');
                $(this).prop('disabled', true);
            } else {
                // Trigger change żeby przeliczyć cenę (np. 2 szt -> 1 szt)
                checkbox.trigger('change');
            }
        }
    });

    // E. Add to Cart
    $('#btn-add-loop').on('click', addToCart);

    // F. Reset Formularza
    $(document).on('pjm_form_reset', function () {
        $('.pjm-loop-form')[0].reset();
        state.days = 0;
        state.distance = 0;
        $('.pjm-addon-checkbox').prop('checked', false);
        $('.pjm-addon-qty-input').val(0); // Reset liczników
        $('.minus').prop('disabled', true);
        $('#loop-schedule-container').empty();
        $('#loop-schedule-panel').hide();
        calculateLoop();
    });


    // --- 3. LOGIKA OBLICZEŃ (MÓZG) ---
    function calculateLoop() {
        // 1. Obliczanie Dni
        const sVal = $('#loop-date-start').val();
        const eVal = $('#loop-date-end').val();

        if (!sVal || !eVal) {
            state.days = 0;
        } else {
            const sDate = new Date(sVal);
            const eDate = new Date(eVal);

            if (eDate < sDate) {
                $('#loop-date-end').val(sVal);
                state.days = 1;
            } else {
                const diffTime = Math.abs(eDate - sDate);
                state.days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            }
            state.start = sVal;
            state.end = eVal;
        }

        updateScheduleUI(state.days);

        // 2. Wybór Pakietu (Tier)
        let selectedTier = null;
        tiersData.sort((a, b) => parseInt(a.min_days) - parseInt(b.min_days));
        const checkDays = state.days > 0 ? state.days : 1;

        for (let tier of tiersData) {
            if (checkDays >= tier.min_days && checkDays <= tier.max_days) {
                selectedTier = tier;
                break;
            }
        }
        if (!selectedTier && tiersData.length > 0) selectedTier = tiersData[tiersData.length - 1];
        state.selectedTier = selectedTier;

        // 3. Koszt Bazowy (Wynajem) - POPRAWIONA LOGIKA
        if (state.days > 0 && selectedTier) {
            let rate = parseFloat(selectedTier.rate);
            let base = parseFloat(selectedTier.base_cost);

            // FIX: Zamiast odejmować min_days, odejmujemy (min_days - 1).
            // Dzięki temu dla 1 dnia (przy min=1) mnożnik wynosi 1, a nie 0.
            let multiplier = state.days - (selectedTier.min_days - 1);
            if (multiplier < 0) multiplier = 0;

            state.rentCost = base + (multiplier * rate);
        } else {
            state.rentCost = 0;
        }

        if (selectedTier) $('#loop-tier-name').text(selectedTier.name);
        $('#loop-rent-cost').text(fmt(state.rentCost));

        // 4. Koszt Dojazdu
        if (state.distance > 0) {
            state.travelCost = (state.distance * 2) * travelRate;
            $('#loop-row-travel').fadeIn();
            $('#loop-preview-travel').text(fmt(state.travelCost));
        } else {
            state.travelCost = 0;
            $('#loop-row-travel').hide();
        }

        // 5. Dodatki (Addons)
        state.addonsCost = 0;
        state.selectedAddons = [];
        let addonsHtml = '';

        const freeAddons = (selectedTier && state.days > 0) ? (selectedTier.free_addons || []) : [];

        $('.pjm-addon-checkbox').each(function () {
            const el = $(this);
            const row = el.closest('.pjm-addon-row');
            const code = el.val();

            const isFree = freeAddons.includes(code);
            if (isFree) row.find('.pjm-badge-included').show();
            else row.find('.pjm-badge-included').hide();

            if (el.is(':checked') || isFree) {
                let price = parseFloat(el.data('price')) || 0;
                const mode = el.data('mode');
                const inputType = el.data('input-type');
                const name = el.data('name');

                let qty = 1;
                if (inputType === 'counter') {
                    qty = parseInt(row.find('.pjm-addon-qty-input').val()) || 0;
                    if (qty === 0) return;
                }

                let itemTotal = 0;

                if (!isFree) {
                    if (mode === 'per_unit') {
                        if (state.days > 0) itemTotal = price * qty * state.days;
                    } else {
                        itemTotal = price * qty;
                    }
                    state.addonsCost += itemTotal;
                }

                if (el.is(':checked') && qty > 0) {
                    let suffix = '';
                    if (qty > 1) suffix += ` (x${qty})`;
                    if (mode === 'per_unit' && state.days > 0) suffix += ` (${state.days} dni)`;

                    state.selectedAddons.push({
                        code: code,
                        name: name + suffix,
                        price: itemTotal,
                        unitPrice: price,
                        qty: qty,
                        mode: mode
                    });

                    addonsHtml += `
                        <div class="summary-row small">
                            <span>${name}${suffix}</span>
                            <span>${isFree ? '0 zł' : fmt(itemTotal)}</span>
                        </div>`;
                }
            }
        });
        $('#loop-addons-list').html(addonsHtml);

        // 6. Suma
        state.totalPrice = state.rentCost + state.travelCost + state.addonsCost;

        $('#loop-days-count').text(state.days + (state.days === 1 ? ' dzień' : ' dni'));
        $('#loop-price-display').text(fmt(state.totalPrice));

        if (state.days > 0) {
            $('#loop-summary-empty').hide();
            $('#loop-summary-content').fadeIn();
        } else {
            $('#loop-summary-empty').show();
            $('#loop-summary-content').hide();
        }
    }
    
    // --- 4. UI HARMONOGRAMU ---
    function updateScheduleUI(days) {
        const container = $('#loop-schedule-container');
        const panel = $('#loop-schedule-panel');

        if (days <= 0) {
            panel.hide();
            container.empty();
            return;
        }

        // Optymalizacja: nie przerysowuj jeśli liczba dni ta sama
        if (container.children().length === days && state.start) {
            return;
        }

        container.empty();
        const startD = new Date(state.start);

        for (let i = 0; i < days; i++) {
            const d = new Date(startD);
            d.setDate(startD.getDate() + i);
            const dateStr = d.toLocaleDateString('pl-PL', { weekday: 'short', day: '2-digit', month: '2-digit' });

            const row = `
                <div class="loop-day-row" style="display:flex; align-items:center; gap:10px; margin-bottom:8px; font-size:13px;">
                    <div style="width:80px; font-weight:600; color:#555;">${dateStr}</div>
                    <input type="time" class="pjm-input loop-day-start" value="09:00" style="padding:5px; border:1px solid #ddd; border-radius:4px;">
                    <span>-</span>
                    <input type="time" class="pjm-input loop-day-end" value="17:00" style="padding:5px; border:1px solid #ddd; border-radius:4px;">
                </div>
            `;
            container.append(row);
        }
        panel.fadeIn();
    }

    // --- 5. POMOCNIKI ---
    function fmt(n) { return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' zł'; }

    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // --- 6. ADD TO CART ---
    function addToCart() {
        if (state.totalPrice <= 0 || state.days === 0) {
            alert('Proszę wybrać daty wynajmu.');
            return;
        }

        if (typeof PJM_Core === 'undefined') {
            console.error('Core JS missing.');
            alert('Błąd systemu. Odśwież stronę.');
            return;
        }

        const city = $('#loop-city').val().trim();
        const street = $('#loop-street').val().trim();
        const zip = $('#loop-zip').val().trim();

        if (city.length < 2 || street.length < 3) {
            alert('Proszę uzupełnić adres instalacji/wysyłki.');
            $('#loop-location-section').find('input').first().focus();
            return;
        }
        const address = `${street}, ${zip} ${city}`;

        // Godziny dostępności pętli (od–do) — do harmonogramu obsługi/kalendarza pętli.
        const tFrom = $('#loop-time-start').val() || '';
        const tTo   = $('#loop-time-end').val() || '';
        const timeStr = (tFrom && tTo) ? ` (${tFrom}-${tTo})` : '';

        // Harmonogram
        const scheduleArr = [];
        $('#loop-schedule-container .loop-day-row').each(function () {
            const dayLabel = $(this).find('div').text();
            const startT = $(this).find('.loop-day-start').val();
            const endT = $(this).find('.loop-day-end').val();
            scheduleArr.push(`${dayLabel} (${startT}-${endT})`);
        });

        // Dojazd jako pozycja dodatkowa
        if (state.travelCost > 0) {
            state.selectedAddons.push({
                code: 'travel_loop',
                name: `Dojazd i logistyka (${state.distance} km)`,
                price: parseFloat(state.travelCost.toFixed(2)),
                qty: 1,
                type: 'service'
            });
        }

        // Budowanie Koszyka
        const cartItem = {
            id: generateUUID(),
            service_type: 'loop',
            title: `Wynajem pętli (${state.start} – ${state.end})`,
            quantity: state.days,
            unit: 'dni',
            pricing: {
                unit_price: 0,
                total: parseFloat(state.totalPrice.toFixed(2))
            },
            delivery: { mode: 'standard' },
            meta: {
                scope: `Pakiet: ${state.selectedTier ? state.selectedTier.name : 'Standard'}`,
                dates: `${state.start} – ${state.end}${timeStr}`,
                time_from: tFrom,
                time_to: tTo,
                location: address,
                distance_km: state.distance,
                schedule: scheduleArr.join('; '),
                addons_list: state.selectedAddons.map(a => a.name).join(', ')
            },
            files: { source_url: "", delivery_url: "" },
            _addons_data: state.selectedAddons // To jest kluczowe dla obliczeń backendowych
        };

        const fd = new FormData();
        fd.append('action', 'pjm_add_cart_item');
        fd.append('cart_item_json', JSON.stringify(cartItem));

        if (typeof pjm_calc_vars !== 'undefined') {
            fd.append('nonce', pjm_calc_vars.nonce);
        }

        const btn = $('#btn-add-loop');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="material-symbols-rounded spin">sync</span> Przetwarzanie...');

        PJM_Core.processAddToCart(fd, 'loop')
            .always(function () {
                btn.prop('disabled', false).html(originalText);
            });
    }

});