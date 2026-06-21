<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// --- 0. BLOKADA CACHE ---
if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );
nocache_headers();

// --- KLASY ---
require_once PJM_CALC_PATH . 'includes/modules/calculator/class-calculator-handler.php';
require_once PJM_CALC_PATH . 'includes/modules/payment/class-wallet-handler.php';

$coupon_path = PJM_CALC_PATH . 'includes/modules/payment/class-coupons.php';
if ( file_exists($coupon_path) ) require_once $coupon_path;

// --- 1. WERYFIKACJA ---
$user_id = get_current_user_id();
if ( ! $user_id ) {
    wp_redirect( site_url( '/logowanie?redirect_to=' . urlencode($_SERVER['REQUEST_URI']) ) );
    exit;
}

global $wpdb;
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

$order = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pjm_orders WHERE id = %d AND user_id = %d",
        $order_id,
        $user_id
    )
);

// --- 2. STATUSY ---
$finished_statuses = ['paid','completed','processing','payment_pending'];
if ( $order && in_array($order->order_status, $finished_statuses, true) ) {
    wp_redirect( site_url('/moje-konto/?tab=thankyou&order_id='.$order_id) );
    exit;
}

if ( ! $order || $order->order_status === 'cancelled' ) {
    wp_redirect( site_url('/moje-konto/?tab=orders') );
    exit;
}

// --- 3. SUBSKRYPCJA ---
$tech_data = json_decode($order->files_json ?? '{}', true);
$is_subscription_order = ($tech_data['type'] ?? '') === 'subscription';
$is_product_order      = ($tech_data['type'] ?? '') === 'product'; // produkt cyfrowy — bez portfela (Gesty są dla usług PJM)

// --- 4. ZAMÓWIENIE ---
$cart_items = json_decode($order->order_json ?? '[]', true);
if ( ! is_array($cart_items) ) $cart_items = [];

$base_price_pln = (float) $order->total_price;

// --- 5. PORTFEL ---
$wallet = new PJM_Wallet_Handler();
$real_user_balance = (float) $wallet->get_user_balance($user_id);

// subskrypcja i produkt cyfrowy = brak użycia portfela
$available_balance = ( $is_subscription_order || $is_product_order ) ? 0.0 : $real_user_balance;

$gesture_value_pln = 500.0;
$gestures_needed   = $base_price_pln / $gesture_value_pln;
$max_wallet_input  = min($available_balance, $gestures_needed);

// --- 6. RABAT ---
$subscriber_discount_rate = 0.0;

if ( ! $is_subscription_order ) {
    $plans_path = PJM_CALC_PATH . 'includes/data/plans.json';
    if ( file_exists($plans_path) ) {
        $plans_data = json_decode(file_get_contents($plans_path), true);
        $active_plan_slug = get_user_meta($user_id, 'pjm_active_plan', true);

        if ( $active_plan_slug && isset($plans_data['plans'][$active_plan_slug]) ) {
            $subscriber_discount_rate = (float) ($plans_data['plans'][$active_plan_slug]['overage_discount'] ?? 0);
        }
    }
}

// --- 7. DANE BILINGOWE ---
$user_settings = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pjm_user_settings WHERE user_id = %d",
        $user_id
    )
);

$wp_user = wp_get_current_user();

$contact_name  = $user_settings->contact_name  ?? $wp_user->display_name;
$contact_email = $user_settings->contact_email ?? $wp_user->user_email;
$contact_phone = $user_settings->contact_phone ?? '';

$company_name = $user_settings->company_name ?? '';
$company_nip  = $user_settings->company_nip  ?? '';

$addr = json_decode($user_settings->address_json ?? '{}', true);

$client_type = $is_subscription_order
    ? 'company'
    : (!empty($company_nip) ? 'company' : 'private');

// --- 8. STRIPE ---
$stripe_pk      = get_option('pjm_stripe_publishable_key', '');
$stripe_enabled = ! empty($stripe_pk);
?>

<div class="jm-checkout-container">
    <form id="pjm-checkout-form">
        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
        
        <div class="jm-checkout-header" style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
            <a href="?tab=calculator" class="jm-btn-back" style="text-decoration:none; color:#777; display:flex; align-items:center;">
                <span class="material-symbols-rounded">arrow_back</span> Wróć
            </a>
            <h2 style="margin:0; font-size: 24px;">Finalizacja zamówienia</h2>
        </div>
        
        <div class="jm-checkout-grid">
            <div class="jm-col-billing">
                
                <div class="jm-card-box" style="margin-bottom: 20px; background:#fff; padding:25px; border-radius:12px; border:1px solid #eee;">
                    <div class="box-header" style="margin-bottom:20px;">
                        <h3 style="margin:0; display:flex; align-items:center; gap:10px;">
                            <span class="material-symbols-rounded" style="color:var(--pjm-orange);">person</span> Dane zamawiającego
                        </h3>
                    </div>
                    <div class="box-content">
                        <div class="jm-form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="jm-input-group" style="grid-column: span 2;">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">Imię i Nazwisko</label>
                                <input type="text" name="contact_name" class="pjm-input" value="<?php echo esc_attr($contact_name); ?>" required style="width:100%;">
                            </div>
                            <div class="jm-input-group">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">E-mail</label>
                                <input type="email" name="contact_email" class="pjm-input" value="<?php echo esc_attr($contact_email); ?>" readonly style="width:100%; background:#f5f7fa; cursor:not-allowed;">
                            </div>
                            <div class="jm-input-group">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">Telefon <span style="color:red;">*</span></label>
                                <input type="text" name="contact_phone" class="pjm-input" value="<?php echo esc_attr($contact_phone); ?>" placeholder="+48..." required style="width:100%;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="jm-card-box billing-box" style="margin-bottom: 20px; background:#fff; padding:25px; border-radius:12px; border:1px solid #eee;">
                    <div class="box-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                        <h3 style="margin:0; display:flex; align-items:center; gap:10px;">
                            <span class="material-symbols-rounded" style="color:var(--pjm-orange);">receipt_long</span> Dane do faktury
                        </h3>
                        
                        <div class="billing-type-switch" style="display:flex; gap:10px; background:#f0f0f0; padding:4px; border-radius:8px;">
                            <label style="cursor:pointer; font-size:12px; padding:5px 10px;">
                                <input type="radio" name="billing_type" value="company" checked> Firma
                            </label>
                            
                            <?php if(!$is_subscription_order): ?>
                            <label style="cursor:pointer; font-size:12px; padding:5px 10px;">
                                <input type="radio" name="billing_type" value="private" <?php checked($client_type, 'private'); ?>> Osoba
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="box-content">
                        <?php if($is_subscription_order): ?>
                            <div style="background:#fff3cd; color:#856404; padding:10px; border-radius:6px; font-size:12px; margin-bottom:15px;">
                                <strong>Uwaga:</strong> Abonament dostępny jest wyłącznie dla firm. Wymagany NIP.
                            </div>
                        <?php else: ?>
                            <div style="margin-bottom:15px;">
                                <label style="font-size:12px; cursor:pointer; color:var(--pjm-orange); font-weight:600;">
                                    <input type="checkbox" id="copy-contact-data"> Użyj danych zamawiającego
                                </label>
                            </div>
                        <?php endif; ?>

                        <div class="jm-form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="jm-input-group" style="grid-column: span 2;">
                                <label id="label_company" style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">Pełna nazwa firmy <span style="color:red;">*</span></label>
                                <input type="text" name="billing_company" id="billing_company" class="pjm-input" value="<?php echo esc_attr($company_name); ?>" style="width:100%;" required>
                            </div>
                            <div class="jm-input-group" style="grid-column: span 2;" id="row_nip">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">NIP <span style="color:red;">*</span></label>
                                <input type="text" name="billing_nip" id="billing_nip" class="pjm-input" value="<?php echo esc_attr($company_nip); ?>" placeholder="Tylko cyfry" style="width:100%;" required>
                            </div>
                            <div class="jm-input-group" style="grid-column: span 2;">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">E-mail do faktur</label>
                                <input type="email" name="billing_email" id="billing_email" class="pjm-input" value="<?php echo esc_attr($addr['email'] ?? $contact_email); ?>" placeholder="np. ksiegowosc@firma.pl" style="width:100%;">
                                <small style="color:#888; font-size:11px;">Na ten adres trafią faktury/proformy (jeśli inny niż konto).</small>
                            </div>
                            <div class="jm-input-group" style="grid-column: span 2;">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">Adres (Ulica i numer) <span style="color:red;">*</span></label>
                                <input type="text" name="billing_street" id="billing_street" class="pjm-input" value="<?php echo esc_attr($addr['street'] ?? ''); ?>" required style="width:100%;">
                            </div>
                            <div class="jm-input-group">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">Kod pocztowy <span style="color:red;">*</span></label>
                                <input type="text" name="billing_zip" id="billing_zip" class="pjm-input" value="<?php echo esc_attr($addr['zip'] ?? ''); ?>" required placeholder="00-000" style="width:100%;">
                            </div>
                            <div class="jm-input-group">
                                <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px;">Miasto <span style="color:red;">*</span></label>
                                <input type="text" name="billing_city" id="billing_city" class="pjm-input" value="<?php echo esc_attr($addr['city'] ?? ''); ?>" required style="width:100%;">
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ( !$is_subscription_order && !$is_product_order && $available_balance > 0 ): ?>
                    <div class="jm-card-box wallet-box" style="margin-bottom: 20px; background:#f0f7ff; padding:20px; border-radius:12px; border:1px solid #b3d7f2;">
                        <div class="box-header" style="margin-bottom:10px;">
                            <h3 style="margin:0; font-size:16px; color:#0056b3; display:flex; align-items:center; gap:10px;">
                                <span class="material-symbols-rounded">account_balance_wallet</span> 
                                Twoje saldo: <strong><?php echo number_format($available_balance, 2, ',', ' '); ?></strong> gestów
                            </h3>
                        </div>
                        <div class="box-content" style="display:flex; flex-wrap:wrap; align-items:center; gap:15px;">
                            <span style="font-size:13px; color:#444;">Zapłać gestami:</span>
                            <input type="number" id="wallet-units-to-use" name="wallet_used_amount" value="0" min="0" max="<?php echo floatval($max_wallet_input); ?>" step="0.01" style="width:120px; padding:8px; border:1px solid #b3d7f2; border-radius:6px; font-weight:700;">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="jm-card-box payment-box" id="payment-methods-section" style="background:#fff; padding:25px; border-radius:12px; border:1px solid #eee;">
                    <div class="box-header" style="margin-bottom:20px;">
                        <h3 style="margin:0;"><span class="material-symbols-rounded" style="color:var(--pjm-orange); vertical-align:middle;">payments</span> Metoda płatności</h3>
                    </div>
                    <div class="box-content payment-options">
                        
                        <?php if($stripe_enabled): ?>
                        <label class="payment-option selected" style="display:flex; align-items:center; gap:15px; padding:15px; border:1px solid #ddd; border-radius:8px; cursor:pointer;">
                            <input type="radio" name="payment_method" value="stripe" checked>
                            <div class="pay-icon" style="font-size:24px; color:#6772e5;"><span class="material-symbols-rounded">credit_card</span></div>
                            <div class="pay-info">
                                <strong style="display:block; color:#333;">
                                    <?php echo $is_subscription_order ? 'Karta płatnicza' : 'Płatność online'; ?>
                                </strong>
                                <span style="font-size:12px; color:#666;">
                                    <?php echo $is_subscription_order ? 'Automatyczne odnawianie.' : 'Karta, BLIK. Prowizja 0%.'; ?>
                                </span>
                            </div>
                        </label>
                        <?php if(!$is_subscription_order): ?>
                            <div id="stripe-element-container" style="display:none; margin-top:15px; background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee;">
                                <div id="stripe-element-mount"></div>
                                <div id="stripe-error-message" style="color:#e74c3c; font-size:12px; margin-top:10px;"></div>
                            </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if(!$is_subscription_order): ?>
                        <label class="payment-option" style="display:flex; align-items:center; gap:15px; padding:15px; border:1px solid #ddd; border-radius:8px; margin-top:10px; cursor:pointer;">
                            <input type="radio" name="payment_method" value="proforma">
                            <div class="pay-icon" style="font-size:24px; color:#777;"><span class="material-symbols-rounded">description</span></div>
                            <div class="pay-info">
                                <strong style="display:block; color:#333;">Faktura Proforma</strong>
                                <span style="font-size:12px; color:#666;">Tradycyjny przelew. PDF na e-mail.</span>
                            </div>
                        </label>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <div class="jm-col-summary">
                <div class="jm-summary-card sticky" style="background:#fff; padding:30px; border-radius:12px; border:1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position:sticky; top:20px;">
                    <div class="summary-header" style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                        <h2 style="margin:0; font-size:20px;">Podsumowanie</h2>
                        <span style="font-size:12px; color:#888;">Zamówienie #<?php echo $order->order_number; ?></span>
                    </div>
                    
                    <div class="summary-details">
                        <div class="order-items-list" style="margin-bottom:20px; max-height:300px; overflow-y:auto;">
                            <?php foreach($cart_items as $item): 
                                $price_total = floatval($item['pricing']['total'] ?? 0);
                                $unit_name = $item['unit'] ?? 'szt.';
                            ?>
                                <div class="detail-item" style="border-bottom:1px dashed #eee; padding-bottom:10px; margin-bottom:10px;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                        <span style="font-weight:600; color:#333;">
                                            <?php echo esc_html($item['title'] ?: $item['invoice_name']); ?>
                                        </span>
                                        <span style="font-weight:700;">
                                            <?php echo number_format($price_total, 2, ',', ' '); ?> zł
                                        </span>
                                    </div>
                                    <div style="font-size:12px; color:#777; margin-bottom:6px;">
                                        Ilość: <?php echo esc_html($item['quantity'] . ' ' . $unit_name); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if(!$is_subscription_order): ?>
                        <div class="coupon-section">
                            </div>
                        <?php endif; ?>
                        
                        <div class="totals-block">
                            <div class="detail-row" style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                <span style="color:#555;">Suma produktów:</span>
                                <span style="font-weight:600;"><?php echo number_format($base_price_pln, 2, ',', ' '); ?> zł</span>
                            </div>

                            <div id="wallet-deduction-row" class="detail-row" style="display:none; justify-content:space-between; margin-bottom:5px; color:#0056b3;">
                                <span style="font-weight:600;" id="wallet-label-text">Opłacono gestami:</span>
                                <span style="font-weight:700;" id="wallet-amount-text">-0,00 zł</span>
                            </div>

                            <div class="detail-divider" style="border-top:1px solid #eee; margin:15px 0;"></div>

                            <div class="detail-row total" style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-weight:700; font-size:16px;">Do zapłaty:</span>
                                <span id="final-total-display" class="total-price" style="font-size:24px; font-weight:800; color:var(--pjm-orange);">
                                    <?php echo number_format($base_price_pln, 2, ',', ' ') . ' zł'; ?>
                                </span>
                            </div>
                            
                            <div style="text-align:right; font-size:11px; color:#777; margin-top:5px;">
                                <?php
                                $vat_disp = 'zw';
                                if ( $is_product_order && ! empty( $cart_items[0]['vat_rate'] ) ) {
                                    $v = $cart_items[0]['vat_rate'];
                                    $vat_disp = ( $v === 'zw' ) ? 'zw' : $v . '%';
                                }
                                ?>
                                Podatek VAT: <strong><?php echo esc_html( $vat_disp === 'zw' ? 'zwolniony' : $vat_disp ); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="terms-section" style="margin-top:25px;">
                        <label style="display:flex; gap:10px; font-size:12px; cursor:pointer; align-items:flex-start;">
                            <input type="checkbox" name="terms" required style="margin-top:3px;">
                            <span>Akceptuję <a href="<?php echo esc_url( home_url( '/regulamin-serwisu/' ) ); ?>" target="_blank" rel="noopener">Regulamin</a>
                            oraz <a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>" target="_blank" rel="noopener">Politykę prywatności</a>.</span>
                        </label>
                        <?php // Zgoda na treści cyfrowe — pokazywana i wymagana przy produktach cyfrowych (utrata prawa odstąpienia po pobraniu). ?>
                        <label class="pjm-consent-digital" style="display:<?php echo $is_product_order ? 'flex' : 'none'; ?>; gap:10px; font-size:12px; cursor:pointer; align-items:flex-start; margin-top:10px;">
                            <input type="checkbox" name="consent_digital" <?php echo $is_product_order ? 'required' : ''; ?> style="margin-top:3px;">
                            <span>Żądam rozpoczęcia dostarczenia treści cyfrowych przed upływem terminu odstąpienia od umowy i przyjmuję do wiadomości utratę prawa odstąpienia po rozpoczęciu pobierania.</span>
                        </label>
                    </div>
                    
                    <button type="button" id="btn-pay-now" class="jm-btn-checkout" style="width:100%; margin-top:20px; padding:15px; background:var(--pjm-orange, #1B5E4B); color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px;">
                        <span class="btn-text">Zamawiam i płacę</span>
                        <span class="material-symbols-rounded">arrow_forward</span>
                    </button>

                    <div style="text-align:center; margin-top:15px; font-size:11px; color:#aaa; display:flex; align-items:center; justify-content:center; gap:5px;">
                        <span class="material-symbols-rounded" style="font-size:14px;">lock</span> Bezpieczna transakcja SSL
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if($stripe_enabled): ?>
    <script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>