jQuery(document).ready(function ($) {

    // Upewnij się, że mamy zmienne
    if (typeof pjm_admin_vars === 'undefined') return;

    // --- 1. MODAL: ODEŚLIJ MATERIAŁY ---
    const $sendModal = $('#pjm-send-modal');

    // Otwieranie modala (Event Delegation)
    $(document).on('click', '.pjm-action-send-back', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const email = $(this).data('email');

        $('#send_order_id').val(id);
        $('#send_client_email').val(email);
        $sendModal.fadeIn('fast').css('display', 'flex');
    });

    // Zamykanie modala
    $sendModal.find('.btn-close-modal').click(function () {
        $sendModal.fadeOut('fast');
    });

    // Wysyłka formularza (AJAX)
    $('#pjm-send-form').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.text();

        $btn.text('Wysyłanie...').prop('disabled', true);

        let formData = new FormData(this);
        formData.append('action', 'pjm_admin_send_back');
        // Nonce powinno być przekazane w pjm_admin_vars

        $.ajax({
            url: pjm_admin_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                if (res.success) {
                    alert('Wiadomość wysłana do klienta!');
                    $sendModal.fadeOut();
                    location.reload();
                } else {
                    alert('Błąd: ' + res.data);
                }
            },
            error: function () { alert('Błąd połączenia.'); },
            complete: function () { $btn.text(originalText).prop('disabled', false); }
        });
    });

    // --- 2. AKCJA: GENERUJ FAKTURĘ (Fakturownia) ---
    $(document).on('click', '.pjm-action-invoice', function (e) {
        e.preventDefault();
        const id = $(this).data('id');

        if (!confirm('Czy na pewno wygenerować fakturę w Fakturowni dla zamówienia #' + id + '?')) return;

        const $btn = $(this);
        const originalText = $btn.text();
        $btn.text('Generowanie...').prop('disabled', true);

        $.post(pjm_admin_vars.ajax_url, {
            action: 'pjm_admin_generate_invoice',
            order_id: id
        }, function (res) {
            $btn.text(originalText).prop('disabled', false);
            if (res.success) {
                if (res.data.msg) alert(res.data.msg);
                window.open(res.data.url, '_blank');
            } else {
                alert('Błąd API: ' + res.data);
            }
        });
    });

});