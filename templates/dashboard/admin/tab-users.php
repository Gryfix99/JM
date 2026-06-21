<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// 1. WYSZUKIWARKA
$search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

// 2. POBIERANIE DANYCH (ID, Email, Nazwa, Saldo) — wartość jako parametr %s (prepare), nie sklejana do SQL.
$base = "SELECT u.ID, u.display_name, u.user_email, m.meta_value as wallet
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->usermeta} m ON u.ID = m.user_id AND m.meta_key = 'pjm_wallet_units'";

if ( $search !== '' ) {
    $like = '%' . $wpdb->esc_like( $search ) . '%';
    $users = $wpdb->get_results( $wpdb->prepare(
        $base . " WHERE (u.display_name LIKE %s OR u.user_email LIKE %s) ORDER BY u.ID DESC LIMIT 50",
        $like, $like
    ) );
} else {
    $users = $wpdb->get_results( $base . " ORDER BY u.ID DESC LIMIT 50" );
}
?>

<div class="ja-card table-card">
    
    <div class="ja-toolbar" style="padding: 20px; border-bottom: 1px solid #eee; display: flex; gap: 15px; align-items:center;">
        <form method="get" style="display:flex; gap:10px; width:100%; max-width:600px;">
            <input type="hidden" name="page" value="pjm-users"> <div class="ja-input-group" style="position:relative; flex:1;">
                <span class="dashicons dashicons-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#999;"></span>
                <input type="text" name="q" value="<?php echo esc_attr($search); ?>" class="ja-input" placeholder="Szukaj klienta (email, nazwisko)..." style="width:100%; padding-left:35px;">
            </div>
            <button type="submit" class="ja-btn btn-primary">Szukaj</button>
            <?php if($search): ?><a href="?page=pjm-users" class="ja-btn">Reset</a><?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="ja-table">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>Klient</th>
                    <th>Saldo (Portfel)</th>
                    <th width="100" style="text-align:right;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($users)): ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:#777;">Brak klientów.</td></tr>
                <?php else: foreach($users as $u): 
                    $wallet = floatval($u->wallet);
                    $initial = strtoupper(substr($u->display_name ?: $u->user_email, 0, 1));
                ?>
                <tr class="row-hover">
                    <td><span style="color:#888;">#<?php echo $u->ID; ?></span></td>
                    <td>
                        <div class="user-cell">
                            <div class="avatar-circle" style="background:#e0e0e0; color:#555;"><?php echo $initial; ?></div>
                            <div class="user-info">
                                <strong><?php echo esc_html($u->display_name ?: 'Brak nazwy'); ?></strong>
                                <small><?php echo esc_html($u->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if($wallet > 0): ?>
                            <span class="ja-badge success" style="font-size:12px;">
                                <?php echo number_format($wallet, 2); ?> pkt
                            </span>
                        <?php else: ?>
                            <span class="ja-badge gray">0.00 pkt</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <button type="button" class="ja-icon-btn open-user-modal" data-uid="<?php echo $u->ID; ?>" title="Edytuj i Portfel">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="user-edit-modal" class="pjm-modal-overlay" style="display:none;">
    <div class="pjm-modal-content">
        
        <div class="pjm-modal-header">
            <div>
                <h3 style="margin:0; font-size:18px; color:#333;">Edycja Klienta</h3>
                <small id="modal-user-label" style="color:#777;">Wczytywanie...</small>
            </div>
            <button type="button" class="close-modal">&times;</button>
        </div>
        
        <div style="display:flex; flex:1; overflow:hidden;">
            
            <div class="pjm-modal-sidebar">
                <button type="button" class="sidebar-tab active" data-target="tab-wallet">
                    <span class="dashicons dashicons-wallet"></span> Portfel / Punkty
                </button>
                <button type="button" class="sidebar-tab" data-target="tab-settings">
                    <span class="dashicons dashicons-admin-settings"></span> Ustawienia B2B
                </button>
                <button type="button" class="sidebar-tab" data-target="tab-rates">
                    <span class="dashicons dashicons-tag"></span> Indywidualne Stawki
                </button>
            </div>

            <div class="pjm-modal-body">
                <form id="user-edit-form">
                    <input type="hidden" name="user_id" id="modal-user-id">

                    <div id="tab-wallet" class="tab-content active">
                        <div class="wallet-display-box">
                            <span style="font-size:12px; color:#666; text-transform:uppercase;">Obecne Saldo</span>
                            <div class="current-balance" id="modal-display-wallet">0.00 pkt</div>
                        </div>

                        <div style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
                            <h4 style="margin:0 0 15px 0;">Operacja na środkach</h4>
                            
                            <div class="pjm-grid-2">
                                <div>
                                    <label class="ja-label">Akcja</label>
                                    <select id="wallet_action" class="ja-input">
                                        <option value="add">Dodaj środki (+)</option>
                                        <option value="remove">Odejmij środki (-)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ja-label">Ilość punktów</label>
                                    <input type="number" id="wallet_amount" step="0.01" min="0.01" class="ja-input" placeholder="0.00">
                                </div>
                            </div>
                            
                            <div style="margin-top:10px;">
                                <label class="ja-label">Opis transakcji (widoczne dla klienta)</label>
                                <input type="text" id="wallet_note" class="ja-input" placeholder="np. Doładowanie, Korekta, Bonus">
                            </div>

                            <button type="button" id="btn-update-wallet" class="ja-btn btn-primary" style="width:100%; margin-top:15px; justify-content:center;">
                                Wykonaj Transakcję
                            </button>
                        </div>
                    </div>

                    <div id="tab-settings" class="tab-content" style="display:none;">
                        <div class="pjm-grid-2">
                            <div>
                                <label class="ja-label">Nazwa Firmy</label>
                                <input type="text" name="company_name" id="input_company_name" class="ja-input">
                            </div>
                            <div>
                                <label class="ja-label">NIP</label>
                                <input type="text" name="company_nip" id="input_company_nip" class="ja-input">
                            </div>
                        </div>

                        <div class="pjm-grid-2" style="margin-top:15px;">
                            <div>
                                <label class="ja-label">Polityka Płatności</label>
                                <select name="payment_policy" id="input_payment_policy" class="ja-input">
                                    <option value="prepaid">Przedpłata (Standard)</option>
                                    <option value="postpaid">Faktura Terminowa</option>
                                </select>
                            </div>
                            <div>
                                <label class="ja-label">Limit Kredytowy (PLN)</label>
                                <input type="number" step="0.01" name="credit_limit" id="input_credit_limit" class="ja-input">
                            </div>
                        </div>
                        
                        <div style="margin-top:15px;">
                            <label class="ja-label">Notatki Administratora (Prywatne)</label>
                            <textarea name="admin_notes" id="input_admin_notes" class="ja-input" rows="3"></textarea>
                        </div>
                    </div>

                    <div id="tab-rates" class="tab-content" style="display:none;">
                        <div class="ja-alert info">
                            Wpisz cenę, aby nadpisać cennik systemowy dla tego klienta. Pozostaw puste, aby używać cen domyślnych.
                        </div>
                        
                        <div class="pjm-grid-2">
                            <div>
                                <label class="ja-label">Tłumaczenie Tekstu (PLN/str)</label>
                                <input type="number" step="0.01" name="rate_text" id="input_rate_text" class="ja-input" placeholder="Domyślnie">
                            </div>
                            <div>
                                <label class="ja-label">Wideo PJM (PLN/min)</label>
                                <input type="number" step="0.01" name="rate_video" id="input_rate_video" class="ja-input" placeholder="Domyślnie">
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <div class="pjm-modal-footer">
            <span id="save-status" style="margin-right:15px; font-weight:600;"></span>
            <button type="button" class="ja-btn close-modal" style="margin-right:10px;">Zamknij</button>
            <button type="button" id="btn-save-details" class="ja-btn btn-primary">Zapisz Ustawienia</button>
        </div>
    </div>
</div>

<style>
    /* BAZA */
    .ja-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-top: 20px; }
    .ja-table { width: 100%; border-collapse: collapse; }
    .ja-table th { background: #f8f9fa; padding: 12px 15px; text-align: left; border-bottom: 2px solid #eee; color: #666; font-size: 12px; text-transform: uppercase; }
    .ja-table td { padding: 12px 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .avatar-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
    .ja-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block; }
    .ja-badge.success { background: #e8f5e9; color: #2e7d32; }
    .ja-badge.gray { background: #f5f5f5; color: #757575; }
    
    /* INPUTY & BUTTONY */
    .ja-input { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
    .ja-label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 4px; }
    .ja-btn { padding: 8px 16px; border: 1px solid #ddd; background: #fff; border-radius: 5px; cursor: pointer; color: #333; font-weight: 600; display: inline-flex; align-items: center; text-decoration: none; }
    .ja-btn.btn-primary { background: #2271b1; color: #fff; border-color: #2271b1; }
    .ja-btn:hover { background: #f0f0f1; }
    .ja-icon-btn { border: 1px solid #ddd; background: #fff; width: 30px; height: 30px; border-radius: 4px; cursor: pointer; color: #555; display: inline-flex; align-items: center; justify-content: center; }
    
    /* MODAL */
    .pjm-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .pjm-modal-content { background: #fff; width: 800px; max-width: 95%; height: 600px; max-height: 90vh; border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    .pjm-modal-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .pjm-modal-sidebar { width: 200px; background: #f3f4f6; border-right: 1px solid #eee; padding: 10px 0; display: flex; flex-direction: column; }
    .pjm-modal-body { flex: 1; padding: 25px; overflow-y: auto; }
    .pjm-modal-footer { padding: 15px 20px; border-top: 1px solid #eee; background: #f8f9fa; text-align: right; }
    .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }

    /* SIDEBAR TABS */
    .sidebar-tab { border: none; background: none; text-align: left; padding: 12px 20px; width: 100%; cursor: pointer; color: #555; font-weight: 500; border-left: 3px solid transparent; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
    .sidebar-tab:hover { background: #e5e7eb; }
    .sidebar-tab.active { background: #fff; border-left-color: #2271b1; color: #2271b1; font-weight: 700; }
    
    /* WALLET BOX */
    .wallet-display-box { background: #e1f5fe; border: 1px solid #b3e5fc; padding: 15px; border-radius: 6px; text-align: center; }
    .current-balance { font-size: 24px; font-weight: 800; color: #0277bd; }
    .pjm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .ja-alert.info { background: #fff8e1; border: 1px solid #ffe0b2; color: #154A3B; padding: 10px; font-size: 13px; border-radius: 4px; margin-bottom: 15px; }
</style>

<script>
jQuery(document).ready(function($) {
    const $modal = $('#user-edit-modal');
    
    // 1. Otwieranie Modala
    $('.open-user-modal').on('click', function() {
        const uid = $(this).data('uid');
        $('#modal-user-id').val(uid);
        $('#modal-user-label').text('ID: ' + uid);
        $('#save-status').text('');
        
        // Reset inputs
        $('#user-edit-form')[0].reset();
        $('#wallet_amount').val('');
        $('#wallet_note').val('');
        
        $modal.fadeIn(200);

        // Pobierz dane usera (ustawienia + saldo)
        $.post(pjm_admin_vars.ajax_url, {
            action: 'pjm_admin_get_user_details',
            user_id: uid,
            nonce: pjm_admin_vars.nonce
        }, function(res) {
            if(res.success) {
                const d = res.data;
                const s = d.settings;
                const r = d.rates;

                $('#modal-user-label').text(d.display_name + ' (' + d.email + ')');
                $('#modal-display-wallet').text(parseFloat(d.wallet).toFixed(2) + ' pkt');
                
                // Wypełnianie pól
                $('#input_company_name').val(s.company_name || '');
                $('#input_company_nip').val(s.company_nip || '');
                $('#input_admin_notes').val(s.admin_notes || '');
                $('#input_payment_policy').val(s.payment_policy || 'prepaid');
                $('#input_credit_limit').val(s.credit_limit || '');
                
                $('#input_rate_text').val(r.text || '');
                $('#input_rate_video').val(r.video || '');
            } else {
                alert('Błąd pobierania danych.');
            }
        });
    });

    // 2. Zamykanie
    $('.close-modal').on('click', function() { $modal.fadeOut(200); });

    // 3. Obsługa Zakładek (Sidebar)
    $('.sidebar-tab').on('click', function() {
        $('.sidebar-tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').hide();
        $('#' + $(this).data('target')).show();
    });

    // 4. AKTUALIZACJA PORTFELA (AJAX)
    $('#btn-update-wallet').on('click', function() {
        const btn = $(this);
        const uid = $('#modal-user-id').val();
        const action = $('#wallet_action').val();
        const amount = $('#wallet_amount').val();
        const note = $('#wallet_note').val();

        if(!amount || amount <= 0) { alert('Wpisz poprawną kwotę.'); return; }

        if(!confirm('Czy na pewno chcesz zmienić saldo tego klienta?')) return;

        btn.prop('disabled', true).text('Przetwarzanie...');

        $.post(pjm_admin_vars.ajax_url, {
            action: 'pjm_admin_manual_wallet_update',
            user_id: uid,
            wallet_action: action, // add / remove
            amount: amount,
            note: note
        }, function(res) {
            btn.prop('disabled', false).text('Wykonaj Transakcję');
            if(res.success) {
                alert('Saldo zaktualizowane!');
                $('#modal-display-wallet').text(parseFloat(res.data.new_balance).toFixed(2) + ' pkt');
                $('#wallet_amount').val('');
                $('#wallet_note').val('');
            } else {
                alert('Błąd: ' + res.data);
            }
        });
    });

    // 5. ZAPIS USTAWIEŃ (AJAX)
    $('#btn-save-details').on('click', function() {
        const btn = $(this);
        const form = $('#user-edit-form');
        
        btn.prop('disabled', true).text('Zapisywanie...');
        
        // Zbieramy dane (oprócz sekcji portfela, która ma osobny handler)
        const fd = new FormData(form[0]);
        fd.append('action', 'pjm_admin_save_user_details');
        fd.append('nonce', pjm_admin_vars.nonce);

        $.ajax({
            url: pjm_admin_vars.ajax_url, type: 'POST', data: fd, processData: false, contentType: false,
            success: function(res) {
                btn.prop('disabled', false).text('Zapisz Ustawienia');
                if(res.success) {
                    $('#save-status').text('Zapisano!').css('color', 'green');
                    setTimeout(() => $('#save-status').text(''), 2000);
                } else {
                    alert('Błąd zapisu.');
                }
            }
        });
    });
});
</script>