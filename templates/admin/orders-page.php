<?php if ( ! defined( 'ABSPATH' ) ) exit; 

// Konfiguracja statusów (Kolory i Etykiety)
$status_map = [
    'draft'           => ['label' => 'Szkic', 'bg' => '#e5e5e5', 'color' => '#333'],
    'pending'         => ['label' => 'Oczekujące', 'bg' => '#ffc107', 'color' => '#333'],
    'payment_pending' => ['label' => 'Czeka na wpłatę', 'bg' => '#fd7e14', 'color' => '#fff'],
    'processing'      => ['label' => 'W trakcie', 'bg' => '#17a2b8', 'color' => '#fff'],
    'paid'            => ['label' => 'Opłacone', 'bg' => '#28a745', 'color' => '#fff'],
    'completed'       => ['label' => 'Zakończone', 'bg' => '#28a745', 'color' => '#fff'],
    'cancelled'       => ['label' => 'Anulowane', 'bg' => '#dc3545', 'color' => '#fff'],
];

// Pobieramy parametry paginacji (zmienne z PJM_Admin_Dashboard)
$current_page = isset($page) ? intval($page) : 1;
$max_pages    = isset($total_pages) ? intval($total_pages) : 1;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Zamówienia PJM</h1>
    <a href="<?php echo admin_url('admin.php?page=pjm-orders'); ?>" class="page-title-action">Odśwież</a>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <div class="alignleft actions">
            </div>
        
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo isset($total) ? $total : count($orders); ?> elementów</span>
            <span class="pagination-links">
                <?php if($current_page > 1): ?>
                    <a class="prev-page button" href="?page=pjm-orders&paged=<?php echo $current_page - 1; ?>">‹</a>
                <?php else: ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                <?php endif; ?>
                
                <span class="paging-input">
                    <span class="current-page"><?php echo $current_page; ?></span> z <span class="total-pages"><?php echo $max_pages; ?></span>
                </span>

                <?php if($current_page < $max_pages): ?>
                    <a class="next-page button" href="?page=pjm-orders&paged=<?php echo $current_page + 1; ?>">›</a>
                <?php else: ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="pjm-orders-table-wrapper" style="background:#fff; border:1px solid #c3c4c7; box-shadow:0 1px 1px rgba(0,0,0,.04);">
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:140px;">Nr Zamówienia</th>
                    <th style="width:140px;">Data</th>
                    <th>Klient</th>
                    <th>Metoda</th>
                    <th style="text-align:right;">Kwota</th>
                    <th style="width:150px;">Status</th>
                    <th style="text-align:right; width:120px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $orders ) ) : ?>
                    <tr><td colspan="8" style="padding:20px; text-align:center; color:#666;">Brak zamówień w systemie.</td></tr>
                <?php else : ?>
                    <?php foreach ( $orders as $order ) : 
                        $user = get_userdata($order->user_id);
                        $client_name = $user ? $user->display_name : 'Gość (ID: '.$order->user_id.')';
                        
                        // Status
                        $st = $order->order_status;
                        $st_config = $status_map[$st] ?? ['label' => $st, 'bg' => '#777', 'color' => '#fff'];
                        
                        // Data
                        $date_fmt = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->created_at ) );
                        
                        // Metoda
                        $method_label = ($order->payment_method === 'stripe') ? 'Stripe' : 'Proforma';
                        if($order->payment_method === 'wallet_full') $method_label = 'Portfel';
                    ?>
                    <tr>
                        <td>#<?php echo $order->id; ?></td>
                        <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                        <td><?php echo esc_html($date_fmt); ?></td>
                        <td>
                            <?php echo esc_html($client_name); ?><br>
                            <small style="color:#888;"><?php echo esc_html($user ? $user->user_email : ''); ?></small>
                        </td>
                        <td><?php echo esc_html($method_label); ?></td>
                        <td style="text-align:right;"><strong><?php echo number_format($order->total_price, 2); ?> zł</strong></td>
                        <td>
                            <span style="background:<?php echo $st_config['bg']; ?>; color:<?php echo $st_config['color']; ?>; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase; display:inline-block; text-align:center; min-width:80px;">
                                <?php echo esc_html($st_config['label']); ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <button class="button button-primary btn-pjm-details" data-id="<?php echo $order->id; ?>">
                                <span class="dashicons dashicons-visibility" style="margin-top:4px;"></span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="pjm-order-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; width:900px; max-width:95%; height:90vh; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.5); display:flex; flex-direction:column; animation: pjmFadeIn 0.2s;">
        
        <div style="padding:15px 20px; background:#f6f7f7; border-bottom:1px solid #dcdcde; display:flex; justify-content:space-between; align-items:center; border-radius:8px 8px 0 0;">
            <h2 style="margin:0; font-size:16px; font-weight:600;">Szczegóły Zamówienia</h2>
            <button type="button" class="button-link btn-modal-close" style="font-size:20px; text-decoration:none;">&times;</button>
        </div>

        <div id="pjm-modal-body" style="padding:20px; overflow-y:auto; flex:1; background:#fff;">
            <div style="text-align:center; padding:50px;">
                <span class="spinner is-active" style="float:none; margin:0;"></span> Ładowanie danych...
            </div>
        </div>

        <div style="padding:15px 20px; background:#f6f7f7; border-top:1px solid #dcdcde; display:flex; justify-content:space-between; align-items:center; border-radius:0 0 8px 8px;">
            <div>
                <button class="button button-link-delete btn-pjm-cancel" data-id="0" style="color:#d63638;">Anuluj zamówienie</button>
            </div>
            <button class="button btn-modal-close">Zamknij</button>
        </div>
    </div>
</div>

<style>
@keyframes pjmFadeIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
</style>

<script>
jQuery(document).ready(function($) {
    var pjmAdminNonce = '<?php echo esc_js( wp_create_nonce( 'pjm_admin_action' ) ); ?>';
    const $modal = $('#pjm-order-modal');
    const $body  = $('#pjm-modal-body');
    const $cancelBtn = $('.btn-pjm-cancel');

    // 1. Otwieranie Modala
    $('.btn-pjm-details').on('click', function(e) {
        e.preventDefault();
        const orderId = $(this).data('id');
        
        $modal.css('display', 'flex').hide().fadeIn(200);
        $body.html('<div style="text-align:center; padding:50px;"><span class="spinner is-active" style="float:none;"></span> Ładowanie...</div>');
        $cancelBtn.data('id', orderId).show(); // Przypisz ID do przycisku anulowania

        // Pobierz dane AJAX-em
        $.post(ajaxurl, {
            action: 'pjm_admin_get_details',
            order_id: orderId,
            nonce: pjmAdminNonce
        }, function(res) {
            if(res.success) {
                $body.html(res.data.html);
                initModalScripts(); // Inicjalizacja skryptów wewnątrz modala (np. generowanie faktury)
            } else {
                $body.html('<p style="color:red; text-align:center;">Błąd: ' + res.data + '</p>');
            }
        });
    });

    // 2. Zamykanie Modala
    $('.btn-modal-close').on('click', function() {
        $modal.fadeOut(200);
    });

    // Zamknij po kliknięciu w tło
    $modal.on('click', function(e) {
        if(e.target === this) $(this).fadeOut(200);
    });

    // 3. Anulowanie Zamówienia
    $cancelBtn.on('click', function() {
        const oid = $(this).data('id');
        if(!confirm('Czy na pewno chcesz anulować to zamówienie? Operacja jest nieodwracalna.')) return;

        $.post(ajaxurl, {
            action: 'pjm_admin_cancel_order',
            order_id: oid,
            nonce: pjmAdminNonce
        }, function(res) {
            if(res.success) {
                alert('Zamówienie anulowane.');
                location.reload();
            } else {
                alert('Błąd: ' + res.data);
            }
        });
    });

    // 4. Skrypty wewnątrz modala (Generowanie Faktury, Zapis daty, Realizacja)
    function initModalScripts() {
        
        // A. Generowanie Faktury
        $('.btn-generate-invoice').on('click', function(e) {
            e.preventDefault();
            const btn = $(this);
            const oid = btn.data('id');
            const kind = btn.data('kind'); // 'proforma' lub 'vat'
            
            btn.prop('disabled', true).text('Generowanie...');
            
            $.post(ajaxurl, {
                action: 'pjm_admin_generate_invoice',
                order_id: oid,
                kind: kind,
                nonce: pjmAdminNonce
            }, function(res) {
                if(res.success) {
                    alert(res.data.message);
                    // Odświeżamy treść modala
                    $('.btn-pjm-details[data-id="'+oid+'"]').trigger('click');
                } else {
                    alert('Błąd: ' + res.data);
                    btn.prop('disabled', false).text(kind === 'vat' ? 'Faktura VAT' : 'Proforma');
                }
            });
        });

        // B. Zapis Daty
        $('.btn-save-deadline').on('click', function() {
            const oid = $(this).data('id');
            const date = $('#pjm_deadline_input').val();
            
            $.post(ajaxurl, { action:'pjm_admin_save_deadline', order_id:oid, deadline:date, nonce:pjmAdminNonce }, function(res) {
                if(res.success) alert('Data zapisana.');
                else alert('Błąd zapisu.');
            });
        });

        // C. Finalizacja (Wysłanie linku)
        $('.btn-complete-order').on('click', function() {
            const oid = $(this).data('id');
            const url = $('#pjm_final_url').val();
            if(!url) { alert('Wpisz link do plików.'); return; }
            
            if(!confirm('Czy na pewno zakończyć zamówienie i wysłać maila do klienta?')) return;

            const btn = $(this);
            btn.prop('disabled', true).text('Wysyłanie...');

            $.post(ajaxurl, { action:'pjm_admin_complete_order', order_id:oid, url:url, nonce:pjmAdminNonce }, function(res) {
                if(res.success) {
                    alert('Zakończono pomyślnie.');
                    location.reload();
                } else {
                    alert('Błąd: ' + res.data);
                    btn.prop('disabled', false).text('Wyślij i Zakończ');
                }
            });
        });
        
        // D. Aktywacja Subskrypcji
        $('.btn-activate-sub').on('click', function() {
            const oid = $(this).data('id');
            if(!confirm('Aktywować ręcznie?')) return;
            $.post(ajaxurl, { action:'pjm_admin_activate_sub', order_id:oid, nonce:pjmAdminNonce }, function(res) {
                if(res.success) { alert('Aktywowano.'); location.reload(); }
            });
        });
    }
});
</script>