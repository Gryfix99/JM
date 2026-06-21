jQuery(document).ready(function ($) {
    const $modal = $('#user-edit-modal');
    const $form = $('#user-edit-form');

    // 1. Otwieranie Modala
    $('.open-user-modal').on('click', function (e) {
        e.preventDefault();
        const uid = $(this).data('uid');

        // Reset UI
        $('#modal-user-id').val(uid);
        $('#modal-user-label').text('Wczytywanie ID: ' + uid);
        $('#modal-display-wallet').text('...');
        $form[0].reset();

        // Domyślnie pierwsza zakładka
        $('.sidebar-tab').removeClass('active');
        $('.sidebar-tab[data-target="tab-wallet"]').addClass('active');
        $('.tab-content').hide();
        $('#tab-wallet').show();
        $('#save-status').text('');

        $modal.fadeIn(200);

        // Pobranie danych z backendu
        $.post(ajaxurl, {
            action: 'pjm_admin_get_user_details',
            user_id: uid
        }, function (res) {
            if (res.success) {
                const d = res.data;
                const s = d.settings;
                const r = d.rates;

                // Nagłówek
                $('#modal-user-label').text(d.display_name + ' (' + d.email + ')');

                // Portfel
                $('#modal-display-wallet').text(parseFloat(d.wallet).toFixed(2) + ' pkt');

                // Ustawienia
                $('#input_company_name').val(s.company_name || '');
                $('#input_company_nip').val(s.company_nip || '');
                $('#input_admin_notes').val(s.admin_notes || '');
                $('#input_payment_policy').val(s.payment_policy || 'prepaid');
                $('#input_credit_limit').val(s.credit_limit || '');

                // Stawki
                $('#input_rate_text').val(r.text || '');
                $('#input_rate_video').val(r.video || '');
            } else {
                alert('Błąd pobierania danych: ' + res.data);
                $modal.fadeOut();
            }
        });
    });

    // 2. Zamykanie Modala
    $('.close-modal').on('click', function () {
        $modal.fadeOut(200);
    });

    // Zamknięcie na ESC
    $(document).keyup(function (e) {
        if (e.key === "Escape") $modal.fadeOut(200);
    });

    // 3. Obsługa Zakładek (Sidebar)
    $('.sidebar-tab').on('click', function () {
        $('.sidebar-tab').removeClass('active');
        $(this).addClass('active');

        const target = $(this).data('target');
        $('.tab-content').hide();
        $('#' + target).fadeIn(200);
    });

    // 4. Operacje na Portfelu (Dodaj/Odejmij)
    $('#btn-update-wallet').on('click', function () {
        const btn = $(this);
        const uid = $('#modal-user-id').val();
        const action = $('#wallet_action').val();
        const amount = $('#wallet_amount').val();
        const note = $('#wallet_note').val();

        if (!amount || amount <= 0) {
            alert('Wpisz poprawną ilość punktów (większą od 0).');
            return;
        }

        const actionText = (action === 'add') ? 'DODAĆ' : 'ODJĄĆ';
        if (!confirm(`Czy na pewno chcesz ${actionText} ${amount} pkt temu użytkownikowi?`)) return;

        btn.prop('disabled', true).text('Przetwarzanie...');

        $.post(ajaxurl, {
            action: 'pjm_admin_manual_wallet_update',
            user_id: uid,
            wallet_action: action,
            amount: amount,
            note: note
        }, function (res) {
            btn.prop('disabled', false).text('Wykonaj Transakcję');

            if (res.success) {
                // Aktualizacja UI
                $('#modal-display-wallet').text(parseFloat(res.data.new_balance).toFixed(2) + ' pkt');
                $('#wallet_amount').val('');
                $('#wallet_note').val('');
                alert('Operacja zakończona sukcesem!');
            } else {
                alert('Błąd: ' + res.data);
            }
        });
    });

    // 5. Zapis Ustawień (Dane i Stawki)
    $('#btn-save-details').on('click', function () {
        const btn = $(this);
        const status = $('#save-status');

        btn.prop('disabled', true).text('Zapisywanie...');
        status.text('');

        const fd = new FormData($form[0]);
        fd.append('action', 'pjm_admin_save_user_details');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                btn.prop('disabled', false).text('Zapisz Ustawienia');

                if (res.success) {
                    status.text('Zapisano pomyślnie!').css('color', 'green');
                    setTimeout(() => status.text(''), 3000);
                } else {
                    alert('Błąd zapisu: ' + res.data);
                }
            }
        });
    });
});