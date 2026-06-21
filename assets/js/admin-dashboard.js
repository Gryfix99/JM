jQuery(document).ready(function($) {

    if (typeof pjm_admin_vars === 'undefined') {
        console.error('PJM Admin: brak pjm_admin_vars.');
        return;
    }

    var ajax = pjm_admin_vars.ajax_url;
    var $modal = $('#ja-order-modal');
    var $modalBody = $('#ja-modal-body');

    // Powiadomienia: toast (z fallbackiem). Typ wnioskowany z treści.
    function notify(m){ var t = /^błąd|^error/i.test(String(m)) ? 'error' : 'success'; (window.pjmToast || window.alert)(m, t); }

    // --- 1. PRZEŁĄCZANIE ZAKŁADEK ---
    $(document).on('click', '.ja-tab-btn', function(e) {
        e.preventDefault();
        $('.ja-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.ja-view').removeClass('active');
        $($(this).data('target')).addClass('active');
    });

    // Aktualizacja wizualna badge'a statusu w wierszu (bez przeładowania).
    function applyRowStatusVisual($tr, status) {
        var $sel = $tr.find('.ja-status-changer');
        if (status) $sel.val(status);
        var $opt = $sel.find('option:selected');
        var $badge = $tr.find('.ja-badge');
        $badge.removeClass('gray blue warning orange success danger').addClass($opt.data('class') || 'gray');
        $badge.find('.ja-badge-label').text($opt.text().trim());
    }

    // --- 2. SZYBKA ZMIANA STATUSU (lista zamówień) — delegowane, bez przeładowania ---
    $(document).on('change', '.ja-status-changer', function() {
        var sel = $(this), id = sel.data('id'), st = sel.val(), $tr = sel.closest('tr');
        sel.prop('disabled', true);
        $.post(ajax, { action: 'pjm_admin_update_status', order_id: id, status: st, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) { applyRowStatusVisual($tr, st); notify('Status zaktualizowany.'); }
                else { notify('Błąd: ' + (res.data || 'nie udało się zmienić statusu.')); }
                sel.prop('disabled', false);
            })
            .fail(function() { notify('Błąd połączenia.'); sel.prop('disabled', false); });
    });

    // --- 2b. USUWANIE ZAMÓWIENIA ---
    $(document).on('click', '.btn-delete-order', function() {
        var id = $(this).data('id'), num = $(this).data('number') || ('#' + id);
        if (!confirm('Usunąć zamówienie ' + num + '? Tej operacji nie można cofnąć.')) return;
        var $b = $(this);
        $b.prop('disabled', true);
        $.post(ajax, { action: 'pjm_admin_delete_order', order_id: id, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) {
                    $b.closest('tr').fadeOut(200, function(){ $(this).remove(); refreshBulkBar(); });
                    notify(res.data && res.data.message ? res.data.message : 'Zamówienie usunięte.');
                }
                else { notify('Błąd: ' + (res.data || '')); $b.prop('disabled', false); }
            })
            .fail(function() { notify('Błąd połączenia.'); $b.prop('disabled', false); });
    });

    // --- 2c. AKCJE GRUPOWE (zaznaczanie + masowe usuwanie/status) — bez przeładowania ---
    function selectedIds() {
        return $('.ja-row-check:checked').map(function(){ return $(this).val(); }).get();
    }
    function refreshBulkBar() {
        var n = $('.ja-row-check:checked').length;
        var total = $('.ja-row-check').length;
        $('#ja-bulk-count').text('Zaznaczono: ' + n);
        $('#ja-bulk-bar').css('display', n > 0 ? 'flex' : 'none');
        $('#ja-select-all').prop('checked', total > 0 && n === total)
                           .prop('indeterminate', n > 0 && n < total);
    }
    $(document).on('change', '#ja-select-all', function() {
        $('.ja-row-check').prop('checked', $(this).prop('checked'));
        refreshBulkBar();
    });
    $(document).on('change', '.ja-row-check', refreshBulkBar);
    $(document).on('click', '#ja-bulk-clear', function() {
        $('.ja-row-check, #ja-select-all').prop('checked', false);
        refreshBulkBar();
    });

    $(document).on('click', '#ja-bulk-delete', function() {
        var ids = selectedIds();
        if (!ids.length) return;
        if (!confirm('Usunąć zaznaczone zamówienia (' + ids.length + ')? Tej operacji nie można cofnąć.')) return;
        var $b = $(this).prop('disabled', true);
        $.post(ajax, { action: 'pjm_admin_bulk_orders', bulk_action: 'delete', ids: ids, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) {
                    (res.data.deleted || []).forEach(function(oid){
                        $('tr[data-id="' + oid + '"]').fadeOut(200, function(){ $(this).remove(); refreshBulkBar(); });
                    });
                    notify(res.data.message || 'Usunięto.');
                } else { notify('Błąd: ' + (res.data || '')); }
            })
            .fail(function(){ notify('Błąd połączenia.'); })
            .always(function(){ $b.prop('disabled', false); });
    });

    $(document).on('click', '#ja-bulk-apply-status', function() {
        var ids = selectedIds(), st = $('#ja-bulk-status').val();
        if (!ids.length) return;
        if (!st) { notify('Błąd: wybierz status.'); return; }
        var $b = $(this).prop('disabled', true);
        $.post(ajax, { action: 'pjm_admin_bulk_orders', bulk_action: 'status', status: st, ids: ids, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) {
                    (res.data.updated || []).forEach(function(oid){
                        applyRowStatusVisual($('tr[data-id="' + oid + '"]'), res.data.status);
                    });
                    $('#ja-bulk-status').val('');
                    notify(res.data.message || 'Zaktualizowano.');
                } else { notify('Błąd: ' + (res.data || '')); }
            })
            .fail(function(){ notify('Błąd połączenia.'); })
            .always(function(){ $b.prop('disabled', false); });
    });

    // --- 3. OTWIERANIE MODALA SZCZEGÓŁÓW (przycisk "oko") ---
    $(document).on('click', '.btn-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!$modal.length) { $modal = $('#ja-order-modal'); $modalBody = $('#ja-modal-body'); }
        $modal.addClass('open');
        $modalBody.html('<div style="text-align:center; padding:40px;"><div class="ja-spinner"></div> Pobieranie danych…</div>');
        $.post(ajax, { action: 'pjm_admin_get_details', order_id: id, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) { $modalBody.html(res.data.html); }
                else { $modalBody.html('<div class="jm-alert error" style="padding:20px;color:#b91c1c;">Błąd: ' + (res.data || 'nieznany') + '</div>'); }
            })
            .fail(function() { $modalBody.html('<div class="jm-alert error" style="padding:20px;color:#b91c1c;">Błąd połączenia z serwerem.</div>'); });
    });

    // Zamykanie modala (krzyżyk / tło / ESC)
    $(document).on('click', '.ja-modal-close', function() { $modal.removeClass('open'); });
    $(document).on('click', '#ja-order-modal', function(e) { if (e.target === this) $modal.removeClass('open'); });
    $(document).on('keyup', function(e) { if (e.key === 'Escape') $modal.removeClass('open'); });

    // =========================================================================
    // 4. AKCJE WEWNĄTRZ MODALA (order-details.php) — DELEGOWANE,
    //    bo treść modala jest wstrzykiwana przez AJAX.
    // =========================================================================

    // 4a. Zakończ zamówienie + wyślij link do plików
    $(document).on('click', '.btn-complete-order', function() {
        var id = $(this).data('id');
        var url = $('#pjm_final_url').val();
        if (!url) { notify('Podaj link do gotowych plików.'); return; }
        var $b = $(this), txt = $b.text();
        $b.prop('disabled', true).text('Wysyłanie…');
        $.post(ajax, { action: 'pjm_admin_complete_order', order_id: id, url: url, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) { notify(res.data.message || 'Zamówienie zrealizowane.'); location.reload(); }
                else { notify('Błąd: ' + (res.data || '')); $b.prop('disabled', false).text(txt); }
            })
            .fail(function() { notify('Błąd połączenia.'); $b.prop('disabled', false).text(txt); });
    });

    // 4b. Zapis terminu realizacji
    $(document).on('click', '.btn-save-deadline', function() {
        var id = $(this).data('id');
        var d = $('#pjm_deadline_input').val();
        var $b = $(this);
        $b.prop('disabled', true);
        $.post(ajax, { action: 'pjm_admin_save_deadline', order_id: id, deadline: d, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) { $b.css('background', '#dcfce7'); setTimeout(function(){ $b.css('background', '').prop('disabled', false); }, 1200); }
                else { notify('Błąd: ' + (res.data || '')); $b.prop('disabled', false); }
            })
            .fail(function() { notify('Błąd połączenia.'); $b.prop('disabled', false); });
    });

    // 4c. Generowanie faktury / proformy (Fakturownia)
    $(document).on('click', '.btn-generate-invoice', function() {
        var id = $(this).data('id'), kind = $(this).data('kind') || 'proforma';
        if (!confirm('Wygenerować dokument (' + kind + ') w Fakturowni?')) return;
        var $b = $(this), txt = $b.text();
        $b.prop('disabled', true).text('Generowanie…');
        $.post(ajax, { action: 'pjm_admin_generate_invoice', order_id: id, kind: kind, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) {
                    if (res.data && res.data.invoice_url) window.open(res.data.invoice_url, '_blank');
                    location.reload();
                } else { notify('Błąd Fakturowni: ' + (res.data || '')); $b.prop('disabled', false).text(txt); }
            })
            .fail(function() { notify('Błąd połączenia.'); $b.prop('disabled', false).text(txt); });
    });

    // 4d. Wymuszenie aktywacji subskrypcji
    $(document).on('click', '.btn-activate-sub', function() {
        var id = $(this).data('id');
        if (!confirm('Wymusić aktywację subskrypcji dla tego zamówienia?')) return;
        var $b = $(this);
        $b.prop('disabled', true);
        $.post(ajax, { action: 'pjm_admin_activate_sub', order_id: id, nonce: pjm_admin_vars.nonce })
            .done(function(res) {
                if (res.success) { notify(res.data.message || 'Subskrypcja aktywowana.'); location.reload(); }
                else { notify('Błąd: ' + (res.data || '')); $b.prop('disabled', false); }
            })
            .fail(function() { notify('Błąd połączenia.'); $b.prop('disabled', false); });
    });

});
