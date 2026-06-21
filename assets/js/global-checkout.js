jQuery(document).ready(function ($) {
    console.log('PJM Checkout v30.0 - Full Logic (Sub + Standard)');
    /* --- DEBUGGER --- */
    setTimeout(function () {
        console.group("PJM Checkout Debugger");
        if (typeof pjm_checkout_vars !== 'undefined') {
            console.log("Status Subskrypcji (is_subscription):", pjm_checkout_vars.is_subscription);
            console.log("Kwota zamówienia:", pjm_checkout_vars.order_total_pln);
            console.log("ID Zamówienia:", pjm_checkout_vars.order_id);
        } else {
            console.error("Zmienne PHP nie zostały załadowane!");
        }

        const nipVal = jQuery('#billing_nip').val();
        console.log("Wartość pola NIP:", nipVal ? nipVal : "PUSTE");
        console.log("Przycisk płatności tekst:", jQuery('#btn-pay-now .btn-text').text());
        console.groupEnd();
    }, 2000);

    // Zabezpieczenie: Sprawdź czy zmienne z PHP istnieją
    if (typeof pjm_checkout_vars === 'undefined') {
        console.error('Błąd krytyczny: Brak zmiennych pjm_checkout_vars.');
        return;
    }
    const vars = pjm_checkout_vars;

    // --- 1. ZMIENNE STANU (Dla zwykłych zamówień) ---
    let baseTotal = parseFloat(vars.order_total_pln) || 0;
    let gestureRate = parseFloat(vars.gesture_rate) || 500;
    let subDiscountRate = parseFloat(vars.subscriber_discount_rate) || 0;

    let currentCouponDiscount = 0;
    let currentGesturesUsed = 0;
    let currentGesturesValuePLN = 0;
    let overageDiscount = 0;
    let finalTotal = baseTotal;

    function formatMoney(amount) {
        return amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' zł';
    }

    // ============================================================
    // TRYB A: SUBSKRYPCJA (SPECJALNA ŚCIEŻKA)
    // ============================================================
    if (vars.is_subscription) {
        console.log('Mode: SUBSCRIPTION DETECTED. Locking UI.');

        // 1. Zablokuj manipulację portfelem (bo nie można płacić gestami za abonament)
        $('#wallet-units-to-use').val(0).prop('disabled', true);
        $('.wallet-box').css('opacity', '0.5').attr('title', 'Niedostępne dla subskrypcji');

        // 2. Dostosuj przycisk płatności
        const $btn = $('#btn-pay-now');
        $btn.css('background', '#6772e5'); // Kolor Stripe
        $btn.find('.btn-text').text('Przejdź do płatności (Stripe)');
        $btn.find('.material-symbols-rounded').text('credit_card');

        // 3. Obsługa kliknięcia "Zapłać" dla Subskrypcji
        $btn.off('click').on('click', function (e) {
            e.preventDefault();

            // A. Walidacja Regulaminu
            if (!$('input[name="terms"]').is(':checked')) {
                alert('Proszę zaakceptować regulamin.');
                return;
            }

            // B. Walidacja NIP (Wymagany dla B2B/Subskrypcji)
            const nipVal = $('#billing_nip').val().trim();
            if (!nipVal) {
                alert('Abonament dostępny jest tylko dla firm. Proszę podać numer NIP.');
                $('#billing_nip').focus().css('border-color', 'red');
                $('html, body').animate({
                    scrollTop: $("#billing_nip").offset().top - 100
                }, 500);
                return;
            } else {
                $('#billing_nip').css('border-color', '#ddd');
            }

            // C. Blokada przycisku
            $btn.prop('disabled', true);
            $btn.find('.btn-text').text('Przetwarzanie danych...');

            // D. KROK 1: Zapisz dane bilingowe w bazie
            const fd = new FormData($('#pjm-checkout-form')[0]);
            fd.append('action', 'pjm_save_billing_data');
            fd.append('nonce', vars.checkout_nonce);

            $.ajax({
                url: vars.ajax_url,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false
            })
                .done(function () {
                    // E. KROK 2: Utwórz sesję Stripe Checkout (AJAX)
                    $btn.find('.btn-text').text('Łączenie ze Stripe...');

                    $.post(vars.ajax_url, {
                        action: 'pjm_pay_subscription',
                        order_id: vars.order_id,
                        nonce: vars.checkout_nonce
                    }, function (res) {
                        if (res.success && res.data.redirect_url) {
                            // F. PRZEKIEROWANIE DO STRIPE
                            window.location.href = res.data.redirect_url;
                        } else {
                            console.error('Stripe Error:', res);
                            alert('Błąd płatności: ' + (res.data.message || 'Nieznany błąd serwera.'));
                            $btn.prop('disabled', false).find('.btn-text').text('Spróbuj ponownie');
                        }
                    }).fail(function (xhr, status, error) {
                        console.error('AJAX Fail:', error);
                        alert('Błąd połączenia z bramką płatności.');
                        $btn.prop('disabled', false).find('.btn-text').text('Spróbuj ponownie');
                    });
                })
                .fail(function () {
                    alert('Nie udało się zapisać danych bilingowych. Spróbuj ponownie.');
                    $btn.prop('disabled', false).find('.btn-text').text('Spróbuj ponownie');
                });
        });

        // STOP: Przerywamy działanie skryptu, aby logika zwykłych zamówień nie nadpisała eventów
        return;
    }


    // ============================================================
    // TRYB B: STANDARDOWE ZAMÓWIENIE (Portfel, Kupony, Proforma)
    // ============================================================

    console.log('Mode: STANDARD ORDER');

    // 1. Obsługa Kuponów
    $('#apply_coupon_btn').on('click', function (e) {
        e.preventDefault();
        const code = $('#coupon_code').val().trim();
        const $btn = $(this);
        if (!code) return;
        $btn.text('...').prop('disabled', true);

        $.post(vars.ajax_url, {
            action: 'pjm_check_coupon',
            coupon_code: code,
            order_total: baseTotal,
            nonce: vars.checkout_nonce
        }, function (res) {
            $btn.text('Użyj').prop('disabled', false);
            if (res.success) {
                currentCouponDiscount = parseFloat(res.data.discount_amount);
                $('#coupon_message').text(res.data.message).css('color', 'green');
                $('#coupon_code').prop('disabled', true);
                $btn.hide();
                recalculate();
            } else {
                $('#coupon_message').text(res.data).css('color', 'red');
                currentCouponDiscount = 0;
                recalculate();
            }
        });
    });

    // 2. Obsługa Pola Gestów
    $('#wallet-units-to-use').on('input change keyup', function () {
        let val = $(this).val().replace(',', '.');
        if (val === '') {
            currentGesturesUsed = 0;
            recalculate();
            return;
        }

        let gestInput = parseFloat(val);
        let max = parseFloat($(this).attr('max'));

        if (isNaN(gestInput)) gestInput = 0;
        if (gestInput < 0) gestInput = 0;
        if (gestInput > max) gestInput = max;

        currentGesturesUsed = gestInput;
        recalculate();
    });

    // 3. Funkcja Przeliczania (Standard)
    function recalculate() {
        // Oblicz wartość użytych gestów
        currentGesturesValuePLN = currentGesturesUsed * gestureRate;

        let remaining = baseTotal - currentGesturesValuePLN;
        if (remaining < 0) remaining = 0;

        // Nalicz rabat subskrybenta od pozostałej kwoty
        overageDiscount = (subDiscountRate > 0 && remaining > 0) ? remaining * subDiscountRate : 0;

        // Finalna kwota
        finalTotal = remaining - overageDiscount - currentCouponDiscount;
        if (isNaN(finalTotal) || finalTotal < 0.05) finalTotal = 0;

        // Aktualizacja UI
        if (currentGesturesValuePLN > 0) {
            $('#wallet-deduction-row').css('display', 'flex');
            $('#wallet-label-text').text('Opłacono gestami (' + currentGesturesUsed + '):');
            $('#wallet-amount-text').text('-' + formatMoney(currentGesturesValuePLN));
        } else { $('#wallet-deduction-row').hide(); }

        if (overageDiscount > 0) {
            $('#subscriber-discount-row').css('display', 'flex');
            $('#subscriber-discount-text').text('-' + formatMoney(overageDiscount));
        } else { $('#subscriber-discount-row').hide(); }

        if (currentCouponDiscount > 0) {
            $('#coupon-discount-row').css('display', 'flex');
            $('#coupon-discount-text').text('-' + formatMoney(currentCouponDiscount));
        } else { $('#coupon-discount-row').hide(); }

        $('#final-total-display').text(formatMoney(finalTotal));

        // Jeśli opłacono w całości gestami/rabatem -> ukryj metody płatności
        if (finalTotal <= 0.05) {
            $('#payment-methods-section').slideUp();
            $('#btn-pay-now .btn-text').text('Potwierdzam zamówienie (Opłacono z portfela)');
        } else {
            $('#payment-methods-section').slideDown();
            $('#btn-pay-now .btn-text').text('Zamawiam i płacę');
        }

        scheduleIntentRefresh(); // kwota się zmieniła → odśwież PaymentIntent (jeśli Stripe aktywny)
    }

    // Pierwsze przeliczenie
    recalculate();

    // 4. Obsługa Stripe Elements (Dla płatności jednorazowych)
    // var (nie let) — recalculate() wywołuje scheduleIntentRefresh() zanim ta linia się wykona; var unika TDZ.
    var stripe = null, elements = null;
    var intentAmount = -1;            // kwota, na którą zbudowano aktualny PaymentIntent
    var intentRefreshTimer = null;

    // Po zmianie portfela/kuponu PaymentIntent ma nieaktualną kwotę — odbuduj go (debounce).
    function scheduleIntentRefresh() {
        if (!stripe) return; // brak intentu = nic do odświeżenia (initStripe zrobi to przy 1. wyborze)
        if ($('input[name="payment_method"]:checked').val() !== 'stripe') return;
        clearTimeout(intentRefreshTimer);
        intentRefreshTimer = setTimeout(function () {
            stripe = null; elements = null; intentAmount = -1;
            $('#stripe-element-mount').empty();
            if (finalTotal > 0.05) initStripe();
        }, 700);
    }

    // Przełączanie metod płatności
    $('input[name="payment_method"]').on('change', function () {
        $('.payment-option').removeClass('selected');
        $(this).closest('.payment-option').addClass('selected');

        if ($(this).val() === 'stripe') {
            $('#stripe-element-container').slideDown();
            initStripe();
        } else {
            $('#stripe-element-container').slideUp();
        }
    });

    // Auto-init jeśli Stripe jest wybrany domyślnie
    if ($('input[name="payment_method"]:checked').val() === 'stripe') {
        $('#stripe-element-container').slideDown();
        initStripe();
    }

    async function initStripe() {
        if (!vars.stripe_pk || finalTotal <= 0.05) return;
        if (stripe) return; // Już zainicjowano

        try {
            stripe = Stripe(vars.stripe_pk);
            // Tworzymy PaymentIntent na backendzie
            const res = await $.post(vars.ajax_url, {
                action: 'pjm_process_order',
                process_type: 'CREATE_INTENT',
                order_id: vars.order_id,
                amount: finalTotal,
                wallet_used_amount: currentGesturesValuePLN,
                applied_coupon: $('#coupon_code').val() || '',
                consent_digital: $('input[name="consent_digital"]').is(':checked') ? 1 : 0,
                nonce: vars.checkout_nonce
            });

            if (res.success && res.data.clientSecret) {
                elements = stripe.elements({
                    clientSecret: res.data.clientSecret,
                    appearance: { theme: 'stripe' }
                });
                const paymentElement = elements.create("payment");
                paymentElement.mount("#stripe-element-mount");
                intentAmount = finalTotal; // zapamiętaj kwotę intentu (do wykrycia rozjazdu przed płatnością)
            }
        } catch (e) { console.error('Stripe Init Error:', e); }
    }

    // 5. Obsługa kliknięcia "Zapłać" (Dla zamówień standardowych)
    $('#btn-pay-now').on('click', async function (e) {
        e.preventDefault();

        if (!$('input[name="terms"]').is(':checked')) {
            alert('Proszę zaakceptować regulamin.');
            return;
        }

        // Zgoda na treści cyfrowe (produkt cyfrowy) — wymagana także na ścieżce Stripe (JS, nie submit HTML).
        const $consent = $('input[name="consent_digital"]');
        if ($consent.length && $consent.prop('required') && !$consent.is(':checked')) {
            alert('Zaznacz zgodę na rozpoczęcie dostarczania treści cyfrowych, aby kontynuować.');
            return;
        }

        const method = (finalTotal <= 0.05) ? 'wallet' : $('input[name="payment_method"]:checked').val();
        const $btn = $(this);
        $btn.prop('disabled', true).find('.btn-text').text('Przetwarzanie...');

        // SCENARIUSZ 1: Płatność Stripe (Elements)
        if (method === 'stripe' && finalTotal > 0.05) {
            // ZABEZPIECZENIE PRZED OVERCHARGE: jeśli intent zbudowano na inną kwotę (zmieniono portfel/kupon),
            // odbuduj go i poproś o ponowne dane karty — nigdy nie potwierdzaj starej (wyższej) kwoty.
            if (!stripe || Math.abs(intentAmount - finalTotal) > 0.01) {
                clearTimeout(intentRefreshTimer);
                stripe = null; elements = null; intentAmount = -1;
                $('#stripe-element-mount').empty();
                await initStripe();
                $('#stripe-error-message').text('Kwota się zmieniła — wprowadź dane karty i kliknij „Zamawiam i płacę" ponownie.');
                $btn.prop('disabled', false).find('.btn-text').text('Zamawiam i płacę');
                return;
            }

            // Zapisz billing
            const fd = new FormData($('#pjm-checkout-form')[0]);
            fd.append('action', 'pjm_save_billing_data');
            fd.append('nonce', vars.checkout_nonce);
            await $.ajax({ url: vars.ajax_url, type: 'POST', data: fd, processData: false, contentType: false });

            // Potwierdź płatność w Stripe
            const returnUrl = window.location.origin + window.location.pathname + '?tab=thankyou&order_id=' + vars.order_id;
            const { error } = await stripe.confirmPayment({ elements, confirmParams: { return_url: returnUrl } });

            if (error) {
                $('#stripe-error-message').text(error.message);
                $btn.prop('disabled', false).find('.btn-text').text('Zamawiam i płacę');
            }

            // SCENARIUSZ 2: Proforma lub Pełna płatność z portfela
        } else {
            const fd = new FormData($('#pjm-checkout-form')[0]);
            fd.append('action', 'pjm_process_order');
            fd.append('process_type', 'FINALIZE_PAYMENT');
            fd.append('final_method', method);
            fd.append('wallet_used_amount', currentGesturesValuePLN);
            fd.append('nonce', vars.checkout_nonce);
            fd.append('applied_coupon', $('#coupon_code').val());

            $.ajax({
                url: vars.ajax_url,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success && res.data.redirect) {
                        window.location.href = res.data.redirect;
                    } else {
                        alert(res.data || 'Wystąpił błąd podczas przetwarzania.');
                        $btn.prop('disabled', false).find('.btn-text').text('Zamawiam i płacę');
                    }
                },
                error: function () {
                    alert('Błąd połączenia z serwerem.');
                    $btn.prop('disabled', false).find('.btn-text').text('Zamawiam i płacę');
                }
            });
        }
    });
});