jQuery(document).ready(function ($) {
    let currentOrderId = 0;
    const modal = $('#pjm-order-modal');
    const modalBody = $('#pjm-modal-body');

    // --- 1. OTWIERANIE MODALA ---
    $('.btn-pjm-details').on('click', function (e) {
        e.preventDefault();
        currentOrderId = $(this).data('id');

        // Pokaż modal i loader
        modal.fadeIn(200);
        modalBody.html('<div style="text-align:center; padding:40px;"><span class="spinner is-active" style="float:none; margin:0;"></span> Ładowanie danych...</div>');

        // Pobierz dane
        $.post(ajaxurl, {
            action: 'pjm_admin_get_details',
            order_id: currentOrderId
        }, function (res) {
            if (res.success) {
                modalBody.html(res.data.html);
            } else {
                modalBody.html('<div class="notice notice-error inline"><p>' + res.data + '</p></div>');
            }
        }).fail(function () {
            modalBody.html('<div class="notice notice-error inline"><p>Błąd połączenia z serwerem.</p></div>');
        });
    });

    // --- 2. ZAMYKANIE MODALA ---
    // Kliknięcie w przycisk zamknij lub X
    modal.on('click', '.notice-dismiss, .btn-modal-close', function (e) {
        e.preventDefault();
        modal.fadeOut(200);
    });

    // Kliknięcie w tło (overlay) zamyka modal
    $(window).on('click', function (e) {
        if ($(e.target).is('#pjm-order-modal')) {
            modal.fadeOut(200);
        }
    });

    // --- 3. GENEROWANIE FAKTURY (Fakturownia) ---
    $('body').on('click', '.btn-pjm-invoice', function (e) {
        e.preventDefault();
        if (!confirm('Czy na pewno chcesz wystawić fakturę w Fakturowni?')) return;

        const $btn = $(this);
        const originalText = $btn.html();

        $btn.prop('disabled', true).text('Przetwarzanie...');

        $.post(ajaxurl, {
            action: 'pjm_admin_generate_invoice',
            order_id: currentOrderId
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                // Odśwież widok modala, aby pokazać link do faktury bez przeładowania strony
                $('.btn-pjm-details[data-id="' + currentOrderId + '"]').click();
            } else {
                alert('Błąd: ' + res.data);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // --- 4. ANULOWANIE ZAMÓWIENIA ---
    $('body').on('click', '.btn-pjm-cancel', function (e) {
        e.preventDefault();
        if (!confirm('Czy na pewno anulować to zamówienie? Operacja jest nieodwracalna.')) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('Anulowanie...');

        $.post(ajaxurl, {
            action: 'pjm_admin_cancel_order',
            order_id: currentOrderId
        }, function () {
            location.reload(); // Przeładuj stronę, aby zaktualizować status w tabeli
        });
    });

    // --- 5. ZAPISYWANIE DEADLINE (Terminu) ---
    $('body').on('click', '.btn-save-deadline', function (e) {
        e.preventDefault();
        const date = $('#pjm_deadline_input').val();
        const $btn = $(this);

        $btn.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'pjm_admin_save_deadline',
            order_id: currentOrderId,
            deadline: date
        }, function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                // Pokaż mały feedback "Zapisano"
                $btn.parent().next('.deadline-feedback').fadeIn().delay(2000).fadeOut();
            } else {
                alert('Błąd zapisu daty.');
            }
        });
    });

    // --- 6. FINALIZACJA ZAMÓWIENIA (Wysłanie linku) ---
    $('body').on('click', '.btn-pjm-complete-order', function (e) {
        e.preventDefault();
        const url = $('#pjm_final_url').val();

        if (!url) {
            alert('Musisz wkleić link do plików (np. WeTransfer)!');
            $('#pjm_final_url').focus();
            return;
        }

        if (!confirm('Czy na pewno zakończyć zamówienie i wysłać link do klienta?')) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('Wysyłanie...');

        $.post(ajaxurl, {
            action: 'pjm_admin_complete_order',
            order_id: currentOrderId,
            url: url
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                location.reload(); // Przeładuj listę, status zmieni się na Completed
            } else {
                alert('Błąd: ' + res.data);
                $btn.prop('disabled', false).text('Wyślij i Zakończ');
            }
        });
    });

    // --- 7. AKTYWACJA SUBSKRYPCJI (Nowość) ---
    $('body').on('click', '.btn-activate-sub', function (e) {
        e.preventDefault();
        if (!confirm('Czy na pewno aktywować tę subskrypcję? Spowoduje to utworzenie wpisu w bazie i zmianę statusu na Opłacone.')) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('Aktywacja...');

        $.post(ajaxurl, {
            action: 'pjm_admin_activate_sub',
            order_id: currentOrderId
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                location.reload(); // Przeładowanie, aby pokazać aktywną subskrypcję
            } else {
                alert('Błąd: ' + res.data);
                $btn.prop('disabled', false).text('Aktywuj Subskrypcję');
            }
        });
    });

});