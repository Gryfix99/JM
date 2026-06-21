<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Pokazujemy CZYTELNY numer zamówienia (order_number), a nie wewnętrzne ID z URL-a.
$order_label = $order_id;
if ( $order_id ) {
    global $wpdb;
    $on = $wpdb->get_var( $wpdb->prepare( "SELECT order_number FROM {$wpdb->prefix}pjm_orders WHERE id = %d", $order_id ) );
    if ( $on ) $order_label = $on;
}
?>

<script>
    var pjm_thankyou_vars = {
        ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>"
    };
</script>

<div class="ja-card pjm-thankyou-wrapper" style="max-width:600px; margin:40px auto; text-align:center;">
    
    <div id="pjm-verifying" style="display:none; padding:40px;">
        <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px auto;"></div>
        <h3 style="color:#333;">Weryfikacja płatności...</h3>
        
    </div>

    <div id="pjm-success" style="display:none; padding:20px;">
        <span class="material-symbols-rounded" style="font-size:64px; color:#27ae60; margin-bottom:15px; display:block;">check_circle</span>
        <h2 style="color:#27ae60; margin-bottom:10px;">Dziękujemy za zamówienie</h2>
        <p style="font-size:16px; color:#444;">Numer zamówienia: <strong>#<?php echo esc_html( $order_label ); ?></strong></p>
        <p style="color:#666; margin-bottom:30px;">Płatność została potwierdzona. Otrzymasz e-mail ze szczegółami.</p>
        
        <div style="display:flex; justify-content:center; gap:15px;">
            <a href="?tab=orders" class="ja-btn btn-primary">Moje zamówienia</a>
            <a href="?tab=calculator" class="ja-btn">Nowe zamówienie</a>
        </div>
    </div>

    <div id="pjm-pending" style="display:none; padding:20px;">
        <span class="material-symbols-rounded" style="font-size:64px; color:#1B5E4B; margin-bottom:15px; display:block;">hourglass_top</span>
        <h2 style="color:#1B5E4B; margin-bottom:10px;">Zamówienie przyjęte</h2>
        <p style="color:#666;">Wybrałeś płatność przelewem.</p>
        <a href="?tab=orders" class="ja-btn btn-primary">Moje zamówienia</a>
    </div>

    <div id="pjm-error" style="display:none; padding:20px;">
        <span class="material-symbols-rounded" style="font-size:64px; color:#e74c3c; margin-bottom:15px; display:block;">error</span>
        <h2 style="color:#e74c3c;">Błąd płatności</h2>
        <p id="pjm-error-msg" style="color:#666;">Nie udało się potwierdzić transakcji.</p>
        <br>
        <a href="?tab=checkout&order_id=<?php echo $order_id; ?>" class="ja-btn">Spróbuj ponownie</a>
    </div>

</div>

<style>
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<script>
jQuery(document).ready(function($) {
    const params = new URLSearchParams(window.location.search);
    const intentId = params.get('payment_intent');
    const redirectStatus = params.get('redirect_status'); 
    const orderId = params.get('order_id');
    const method = params.get('method');

    // SCENARIUSZ A: Powrót ze Stripe
    if (intentId && redirectStatus) {
        
        if (redirectStatus === 'succeeded') {
            $('#pjm-verifying').show();

            // Używamy zmiennej pjm_thankyou_vars.ajax_url zdefiniowanej wyżej
            $.post(pjm_thankyou_vars.ajax_url, {
                action: 'pjm_verify_stripe',
                payment_intent: intentId,
                order_id: orderId
            }, function(res) {
                $('#pjm-verifying').hide();
                
                if (res.success) {
                    $('#pjm-success').fadeIn();
                    // Czyścimy URL
                    const newUrl = window.location.pathname + '?tab=thankyou&order_id=' + orderId;
                    window.history.replaceState({}, document.title, newUrl);
                } else {
                    console.error("Backend Error:", res);
                    $('#pjm-error-msg').text(res.data || 'Weryfikacja nieudana.');
                    $('#pjm-error').fadeIn();
                }
            }).fail(function(xhr, status, error) {
                $('#pjm-verifying').hide();
                console.error("AJAX Fail:", status, error, xhr.responseText);
                $('#pjm-error-msg').text('Błąd połączenia z serwerem. Sprawdź konsolę (F12).');
                $('#pjm-error').fadeIn();
            });

        } else {
            $('#pjm-error-msg').text('Płatność nie została ukończona (Status: ' + redirectStatus + ').');
            $('#pjm-error').fadeIn();
        }
    } 
    // SCENARIUSZ B: Proforma
    else if (method === 'proforma') {
        $('#pjm-pending').show();
    }
    // SCENARIUSZ C: Zwykłe wejście
    else {
        $('#pjm-pending').show(); 
    }
});
</script>