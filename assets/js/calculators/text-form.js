jQuery(document).ready(function ($) {

    // --- 1. KONFIGURACJA I INICJALIZACJA ---
    if ($('.pjm-text-form-pro').length === 0) return;

    const configEl = document.getElementById('pjm-text-tiers-data');
    if (!configEl) return;

    let tiersData = [];
    try {
        tiersData = JSON.parse(configEl.dataset.tiers || '[]');
    } catch (e) {
        console.error("Błąd parsowania tiers JSON:", e);
    }

    const divisor = parseFloat(configEl.dataset.divisor || 1800);

    const state = {
        chars: 0,
        pages: 0,
        baseCost: 0,
        addonsCost: 0,
        totalPrice: 0,
        selectedTier: null,
        selectedAddons: [],
        uploadedFile: null,
        deliveryMode: 'standard'
    };

    // --- 2. LOGIKA OBLICZEŃ (CORE) ---

    function calculateText() {
        // Reset UI jeśli brak znaków
        if (state.chars <= 0) {
            $('#summary-empty').show();
            $('#summary-content').hide();
            return;
        }

        // 1. Obliczanie stron
        state.pages = Math.ceil(state.chars / divisor);
        if (state.pages < 1) state.pages = 1;
        $('#text-pages-manual').val(state.pages);

        // 2. Znalezienie Pakietu (Tier)
        let selectedTier = null;
        // Sortujemy rosnąco po min_pages
        tiersData.sort((a, b) => a.min_pages - b.min_pages);

        for (let tier of tiersData) {
            // Sprawdzamy zakres (włącznie)
            if (state.pages >= tier.min_pages && state.pages <= tier.max_pages) {
                selectedTier = tier;
                break;
            }
        }
        // Fallback do ostatniego
        if (!selectedTier && tiersData.length > 0) {
            selectedTier = tiersData[tiersData.length - 1];
        }

        state.selectedTier = selectedTier;

        // 3. Obliczanie Ceny Bazowej
        if (selectedTier) {
            const rate = parseFloat(selectedTier.rate);
            const baseCost = parseFloat(selectedTier.base || 0);
            const minPages = parseInt(selectedTier.min_pages);

            // Logika: Base (pokrywa wejście w próg) + (Strony ponad minimum * Stawka)
            // Przykład: Próg 2-5. Base 100. Rate 20.
            // 2 strony: 100 + (2-2)*20 = 100.
            // 3 strony: 100 + (3-2)*20 = 120.

            let extraPages = Math.max(0, state.pages - minPages);
            state.baseCost = baseCost + (extraPages * rate);
        } else {
            state.baseCost = 0;
        }

        // 4. Obliczanie Dodatków
        state.addonsCost = 0;
        state.selectedAddons = [];
        let addonsHtml = '';
        const freeAddons = (selectedTier && selectedTier.free_addons) ? selectedTier.free_addons : [];

        $('.pjm-addon-checkbox:checked').each(function () {
            const row = $(this).closest('.pjm-addon-row');
            const id = this.value;
            const name = $(this).data('name');
            const price = parseFloat($(this).data('price')) || 0;
            const mode = $(this).data('mode'); // 'per_unit' lub 'flat'

            let qty = 1;
            if (row.data('type') === 'counter') {
                qty = parseInt(row.find('.pjm-addon-qty-input').val()) || 1;
            }

            const isFree = freeAddons.includes(id);
            let itemCost = 0;

            if (!isFree) {
                if (mode === 'per_unit') {
                    // Cena * Strony * Ilość
                    itemCost = price * state.pages * qty;
                } else {
                    // Cena * Ilość (jednorazowo)
                    itemCost = price * qty;
                }
                state.addonsCost += itemCost;
            } else {
                // Oznacz wizualnie jako gratis (opcjonalnie)
                row.find('.pjm-badge-included').show();
            }

            if (!isFree) row.find('.pjm-badge-included').hide();

            state.selectedAddons.push({
                code: id,
                name: name + (qty > 1 ? ` (x${qty})` : ''),
                price: itemCost,
                qty: qty
            });

            addonsHtml += `
                <div style="display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:4px;">
                    <span>${name} ${qty > 1 ? `(x${qty})` : ''}</span>
                    <span>${isFree ? '<strong style="color:#27ae60">Gratis</strong>' : formatMoney(itemCost)}</span>
                </div>`;
        });

        // 5. Suma i Ekspres
        let subTotal = state.baseCost + state.addonsCost;
        let multiplier = 1.0;

        if (state.deliveryMode === 'express_48h') multiplier = 1.20;
        else if (state.deliveryMode === 'express_24h') multiplier = 1.40;

        state.totalPrice = subTotal * multiplier;

        // 6. UI Update
        $('#summary-empty').hide();
        $('#summary-content').show();

        $('#base-cost-display').text(formatMoney(state.baseCost));
        $('#pages-count-display').text(`${state.pages} str.`);
        $('#tier-name-display').text(selectedTier ? selectedTier.name : '--');

        $('#addons-summary-list').html(addonsHtml);
        $('#text-price-display').text(formatMoney(state.totalPrice));

        if (multiplier > 1) {
            const extra = state.totalPrice - subTotal;
            $('#delivery-info').html(`<small>+${formatMoney(extra)} (Ekspres)</small>`);
        } else {
            $('#delivery-info').empty();
        }
    }

    function formatMoney(amount) {
        return amount.toLocaleString('pl-PL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' zł';
    }

    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // --- 3. OBSŁUGA UI / INPUTÓW ---

    // Wklejanie tekstu
    let typeTimer;
    $('#text-paste-area').on('input', function () {
        clearTimeout(typeTimer);
        const val = $(this).val();
        typeTimer = setTimeout(() => {
            state.chars = val.replace(/\s+/g, ' ').length;
            $('#text-chars').val(state.chars);
            calculateText();
        }, 300);
    });

    // Ręczna zmiana znaków
    $('#text-chars').on('input change', function () {
        state.chars = parseInt($(this).val()) || 0;
        calculateText();
    });

    // Zmiana trybu
    $('input[name="delivery_mode"]').on('change', function () {
        state.deliveryMode = $(this).val();
        calculateText();
    });

    // Checkboxy
    $('.pjm-text-form-pro').on('change', '.pjm-addon-checkbox', function () {
        const row = $(this).closest('.pjm-addon-row');
        if (!this.checked && row.data('type') === 'counter') {
            row.find('.pjm-addon-qty-input').val(0);
            row.find('.minus').prop('disabled', true);
        } else if (this.checked && row.data('type') === 'counter') {
            const qtyInput = row.find('.pjm-addon-qty-input');
            if (parseInt(qtyInput.val()) === 0) {
                qtyInput.val(1);
                row.find('.minus').prop('disabled', false);
            }
        }
        calculateText();
    });

    // Liczniki +/-
    $('.pjm-text-form-pro').on('click', '.plus', function (e) {
        e.preventDefault(); e.stopPropagation();
        const row = $(this).closest('.pjm-addon-row');
        const input = row.find('.pjm-addon-qty-input');
        const cb = row.find('.pjm-addon-checkbox');
        let val = parseInt(input.val()) || 0;
        input.val(++val);
        cb.prop('checked', true);
        row.find('.minus').prop('disabled', false);
        calculateText();
    });

    $('.pjm-text-form-pro').on('click', '.minus', function (e) {
        e.preventDefault(); e.stopPropagation();
        const row = $(this).closest('.pjm-addon-row');
        const input = row.find('.pjm-addon-qty-input');
        const cb = row.find('.pjm-addon-checkbox');
        let val = parseInt(input.val()) || 0;
        if (val > 0) {
            input.val(--val);
            if (val === 0) {
                cb.prop('checked', false);
                $(this).prop('disabled', true);
            }
            calculateText();
        }
    });

    // Kliknięcie w rząd (UX)
    $('.pjm-text-form-pro').on('click', '.pjm-addon-row', function (e) {
        if ($(e.target).is('input, button, a, label, textarea, .plus, .minus')) return;
        const cb = $(this).find('.pjm-addon-checkbox');
        if (cb.length) {
            // Dla liczników: jeśli nie zaznaczone, klik w tło robi +1
            if ($(this).data('type') === 'counter' && !cb.prop('checked')) {
                $(this).find('.plus').trigger('click');
            } else if ($(this).data('type') !== 'counter') {
                cb.prop('checked', !cb.prop('checked')).trigger('change');
            }
        }
    });

    // Taby
    $('.source-tab').on('click', function (e) {
        e.preventDefault();
        $('.source-tab').removeClass('active');
        $('.source-content').hide();

        $(this).addClass('active');
        const target = $(this).data('target');
        $('#' + target).fadeIn(200);

        // Zmień styl active dla przycisku
        $('.source-tab').css('background', '#f8f9fa');
        $(this).css('background', '#fff'); // Active style
    });

    // --- 4. OBSŁUGA PLIKÓW ---

    const fileInput = document.getElementById('text-main-file');
    const dropzone = document.getElementById('file-dropzone');
    const analysisResult = document.getElementById('file-analysis-result');

    if (fileInput) fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));

    if (dropzone) {
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.style.background = '#f0f9ff'; });
        dropzone.addEventListener('dragleave', () => dropzone.style.background = '#fafafa');
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.background = '#fafafa';
            if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
        });
    }

    async function handleFile(file) {
        if (!file) return;
        state.uploadedFile = file;

        if (analysisResult) $(analysisResult).html('<span style="color:#666">Analizuję plik...</span>');

        try {
            let text = "";
            const ext = file.name.split('.').pop().toLowerCase();

            if (ext === 'txt') {
                text = await file.text();
            } else if (ext === 'docx') {
                if (typeof mammoth === 'undefined') throw new Error("Biblioteka Mammoth niedostępna.");
                const arr = await file.arrayBuffer();
                const res = await mammoth.extractRawText({ arrayBuffer: arr });
                text = res.value;
            } else if (ext === 'pdf') {
                if (typeof pdfjsLib === 'undefined') throw new Error("Biblioteka PDF.js niedostępna.");
                const arr = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument(arr).promise;
                for (let i = 1; i <= pdf.numPages; i++) {
                    const p = await pdf.getPage(i);
                    const c = await p.getTextContent();
                    text += c.items.map(item => item.str).join(' ');
                }
            } else {
                throw new Error("Format nieobsługiwany.");
            }

            state.chars = text.replace(/\s+/g, ' ').length;
            $('#text-chars').val(state.chars);
            if (analysisResult) $(analysisResult).html(`<span style="color:#27ae60">Wczytano: ${file.name} (${state.chars} ZZS)</span>`);
            calculateText();

        } catch (e) {
            console.error(e);
            if (analysisResult) $(analysisResult).html(`<span style="color:#c0392b">Błąd: ${e.message}</span>`);
        }
    }

    // --- 5. ADD TO CART ---
    $('#btn-add-text').on('click', function () {
        if (state.totalPrice <= 0) return;

        if (typeof PJM_Core === 'undefined') {
            alert('Core JS Error'); return;
        }

        const cartItem = {
            id: generateUUID(),
            service_type: 'text',
            title: $('#text-title').val() || 'Tekst bez tytułu',
            quantity: state.pages,
            unit: 'str.',
            pricing: { total: parseFloat(state.totalPrice.toFixed(2)) },
            delivery: { mode: state.deliveryMode },
            meta: {
                scope: state.selectedTier ? state.selectedTier.name : 'Standard',
                notes: $('#text-notes').val(),
                chars_count: state.chars,
                addons_list: state.selectedAddons.map(a => a.name).join(', ')
            },
            _addons_data: state.selectedAddons
        };

        const fd = new FormData();
        fd.append('action', 'pjm_add_cart_item');
        if (typeof pjm_vars !== 'undefined') fd.append('nonce', pjm_vars.nonce);

        if (state.uploadedFile) fd.append('main_file', state.uploadedFile);

        // Logo / Zip
        const logo = $('#text-logo')[0]?.files[0];
        if (logo) fd.append('logo_file', logo);
        const zip = $('#text-zip')[0]?.files[0];
        if (zip) fd.append('zip_file', zip);

        fd.append('cart_item_json', JSON.stringify(cartItem));

        PJM_Core.processAddToCart(fd, 'text');
    });

    // Start
    calculateText();
});