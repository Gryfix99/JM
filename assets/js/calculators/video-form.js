jQuery(document).ready(function ($) {

    // --- 1. KONFIGURACJA ---
    if ($('.pjm-video-form').length === 0) return;

    const configEl = document.getElementById('pjm-video-tiers-data');
    if (!configEl) return;

    let tiersData = [];
    try { tiersData = JSON.parse(configEl.dataset.tiers || '[]'); } catch (e) { }

    const state = {
        mins: 0,
        qty: 1,
        selectedTier: null,
        baseCostPerUnit: 0, // Cena bazowa za 1 film
        addonsCostPerUnit: 0, // Cena dodatków za 1 film
        totalPrice: 0,
        selectedAddons: [],
        uploadedFile: null,
        deliveryMode: 'standard'
    };

    // --- 2. LISTENERS ---

    // A. Kliknięcie w rząd (Toggle Checkbox)
    $('.pjm-video-form').on('click', '.pjm-addon-row', function (e) {
        // Ignoruj kliknięcia w elementy interaktywne
        if ($(e.target).is('input, button, label, .plus, .minus')) return;

        const checkbox = $(this).find('.pjm-addon-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // B. Obsługa liczników (+/-) z blokadą propagacji
    $('.pjm-video-form').on('click', '.plus, .minus', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Zapobiega kliknięciu w rząd

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
        checkbox.prop('checked', val > 0);
        $(this).parent().find('.minus').prop('disabled', val === 0);
        calculateVideo();
    });

    // C. Inputy podstawowe
    $('#video-duration').on('input', function () {
        const val = parseFloat($(this).val());
        state.mins = val > 0 ? Math.ceil(val) : 0;
        calculateVideo();
    });

    $('#video-qty').on('input', function () {
        let val = parseInt($(this).val());
        state.qty = (val > 0) ? val : 1;
        calculateVideo();
    });

    // D. Checkboxy i Pliki
    $('.pjm-video-form').on('change', '.pjm-addon-checkbox', function () {
        calculateVideo();
    });

    $('#video-file').on('change', function (e) {
        state.uploadedFile = e.target.files.length > 0 ? e.target.files[0] : null;
    });

    $('input[name="delivery_mode"]').on('change', function () {
        state.deliveryMode = $(this).val();
        calculateVideo();
    });

    $('#btn-add-video').on('click', addToCart);


    // --- 3. LOGIKA OBLICZEŃ ---

    function calculateVideo() {
        if (state.mins <= 0) {
            $('#video-summary-empty').show();
            $('#video-summary-content').hide();
            $('#video-tier-feedback').hide();
            return;
        }

        // 1. Znajdź Pakiet
        tiersData.sort((a, b) => parseInt(a.limit) - parseInt(b.limit));
        let foundTier = null;
        for (let tier of tiersData) {
            if (state.mins <= parseInt(tier.limit)) {
                foundTier = tier;
                break;
            }
        }

        if (!foundTier) {
            $('#video-tier-error').show();
            $('#video-tier-feedback').hide();
            $('#video-summary-content').hide();
            return;
        }
        state.selectedTier = foundTier;

        // 2. Cena Bazowa (za 1 film)
        let base = parseFloat(foundTier.base_price || 0);
        let nextRate = parseFloat(foundTier.next_minute_price || 0);
        let startMin = parseInt(foundTier.start_minute || 0);
        let chargeableMins = Math.max(0, state.mins - startMin);

        let tierCost = base + (chargeableMins * nextRate);
        state.baseCostPerUnit = tierCost;

        // UI Feedback (Tier)
        $('#video-tier-feedback').show();
        $('#video-tier-error').hide();
        $('#tier-name-display').text(foundTier.name);
        $('#tier-base-cost-display').text(fmt(tierCost));

        // 3. Dodatki (z uwzględnieniem ilości filmów)
        state.addonsCostPerUnit = 0;
        state.selectedAddons = [];
        let addonsHtml = '';
        const freeAddons = foundTier.free_addons || [];

        $('.pjm-video-form .pjm-addon-checkbox').each(function () {
            const el = $(this);
            const code = el.val();
            const isFree = freeAddons.includes(code);
            const badge = el.closest('.pjm-addon-row').find('.pjm-badge-included');

            if (isFree) badge.show(); else badge.hide();

            if (el.is(':checked') || isFree) {
                const price = parseFloat(el.data('price'));
                const mode = el.data('mode'); // 'flat' (za szt.) lub 'per_unit' (za minutę)
                const name = el.data('name');

                let totalItemCost = 0; // Koszt dla WSZYSTKICH filmów

                if (!isFree) {
                    if (mode === 'per_unit') {
                        // np. Napisy: Cena * Minuty * Ilość Filmów
                        totalItemCost = price * state.mins * state.qty;
                    } else {
                        // np. Certyfikat: Cena * Ilość Filmów
                        totalItemCost = price * state.qty;
                    }
                    state.addonsCostPerUnit += totalItemCost;
                }

                if (el.is(':checked')) {
                    // Budowanie nazwy dla koszyka
                    let nameSuffix = '';
                    if (state.qty > 1) {
                        nameSuffix = mode === 'per_unit'
                            ? ` (${state.qty} x ${state.mins} min)`
                            : ` (x${state.qty})`;
                    }

                    state.selectedAddons.push({
                        code: code,
                        name: name + nameSuffix,
                        price: totalItemCost,
                        mode: mode,
                        isFree: isFree
                    });

                    addonsHtml += `
                        <div class="summary-row small">
                            <span>${name}${nameSuffix}</span>
                            <span>${isFree ? '0 zł' : fmt(totalItemCost)}</span>
                        </div>`;
                }
            }
        });

        // 4. Suma Całkowita
        // (Koszt bazy * ilość) + (Koszt dodatków [już policzony dla wszystkich])
        let totalBase = state.baseCostPerUnit * state.qty;
        let subTotal = totalBase + state.addonsCostPerUnit;

        // Mnożnik Ekspres
        let multiplier = 1.0;
        if (state.deliveryMode === 'express_48h') multiplier = 1.20;
        else if (state.deliveryMode === 'express_24h') multiplier = 1.40;

        state.totalPrice = subTotal * multiplier;

        // 5. UI Updates
        $('#video-summary-empty').hide();
        $('#video-summary-content').show();

        // Wyświetlamy koszt BAZOWY (za usługę tłumaczenia wszystkich filmów)
        $('#video-sidebar-base-cost').text(fmt(totalBase));
        $('#video-sidebar-duration').text(`${state.mins} min`);
        $('#video-sidebar-qty').text(`${state.qty} szt.`);

        $('#video-addons-summary-list').html(addonsHtml);
        $('#video-price-display').text(fmt(state.totalPrice));

        // Info o dopłacie
        if (multiplier > 1) {
            const extra = state.totalPrice - subTotal;
            if ($('#video-delivery-info').length) {
                $('#video-delivery-info').html(`<small>Dopłata ekspres: +${fmt(extra)}</small>`);
            }
        } else {
            $('#video-delivery-info').empty();
        }
    }

    function fmt(n) { return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' zł'; }

    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // --- 4. ADD TO CART ---

    function addToCart() {
        if (state.totalPrice <= 0) return;

        if (typeof PJM_Core === 'undefined' || typeof PJM_Core.processAddToCart !== 'function') {
            alert('Błąd: Nie załadowano głównego skryptu kalkulatora.');
            return;
        }

        const titleInput = $('#video-title').val() || 'Wideo bez tytułu';
        const notes = $('#video-notes').val();
        const videoLink = $('#video-link').val();

        const totalDuration = state.mins * state.qty;

        const cartItem = {
            id: generateUUID(),
            service_type: 'video',
            title: titleInput,

            quantity: state.qty,
            unit: 'szt.',

            pricing: {
                total: parseFloat(state.totalPrice.toFixed(2))
            },

            delivery: {
                mode: state.deliveryMode
            },

            meta: {
                scope: state.selectedTier ? state.selectedTier.name : 'Standard',
                duration: `${state.qty} filmy × ${state.mins} min (Łącznie: ${totalDuration} min)`,
                duration_val: state.mins,
                notes: notes,
                delivery_format: "PJM",
                addons_list: state.selectedAddons.map(a => a.name).join(', ')
            },

            files: {
                source_url: videoLink || "",
                delivery_url: ""
            },

            _addons_data: state.selectedAddons
        };

        const formData = new FormData();
        formData.append('action', 'pjm_add_cart_item');
        if (typeof pjm_calc_vars !== 'undefined') formData.append('nonce', pjm_calc_vars.nonce);

        if (state.uploadedFile) {
            formData.append('main_file', state.uploadedFile);
        }
        formData.append('cart_item_json', JSON.stringify(cartItem));

        PJM_Core.processAddToCart(formData, 'video');
    }
});