<?php if ( ! defined( 'ABSPATH' ) ) exit; 
/*
 * Template: Modern Order Details
 * Author: PJM System
 */

// --- 1. PRZYGOTOWANIE DANYCH ---
$cart_items   = json_decode($order->order_json, true) ?: [];
$invoice_data = json_decode($order->invoice_data, true) ?: [];
$files_data   = json_decode($order->files_json, true) ?: [];

// Finanse
$total_price    = floatval($order->total_price);
$discount_value = floatval($order->discount_value);
$subtotal       = $total_price + $discount_value;

// Statusy — z centralnych słowników (te same etykiety w panelu, podglądzie i mailach).
$is_subscription = (strpos($order->package_id, 'subscription') !== false);
$payment_label   = function_exists('pjm_payment_method_label') ? pjm_payment_method_label($order->payment_method) : $order->payment_method;
$pay_status_lbl  = function_exists('pjm_payment_status_label') ? pjm_payment_status_label($order->payment_status) : $order->payment_status;
$status_label    = function_exists('pjm_order_status_label') ? pjm_order_status_label($order->order_status) : $order->order_status;
$status_color    = function_exists('pjm_order_status_color') ? pjm_order_status_color($order->order_status) : '#6b7280';

// Link do realizacji
$deliverable_url = $files_data['deliverable_url'] ?? '';
?>

<style>
    .pjm-order-view { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.5; background: #f3f4f6; padding: 20px; box-sizing: border-box; }
    .pjm-order-view * { box-sizing: border-box; }
    
    /* Layout */
    .pjm-grid { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; }
    .pjm-col-main { flex: 2; min-width: 300px; }
    .pjm-col-side { flex: 1; min-width: 280px; }
    
    /* Cards */
    .pjm-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #e5e7eb; }
    .pjm-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px; }
    .pjm-card-title { font-size: 16px; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px; }
    
    /* Typography */
    .pjm-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; display: block; }
    .pjm-value { font-size: 14px; font-weight: 500; color: #111827; }
    .pjm-small { font-size: 13px; color: #6b7280; }
    
    /* Badges */
    .pjm-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; color: #fff; text-transform: uppercase; }
    
    /* Tables */
    .pjm-table { width: 100%; border-collapse: collapse; }
    .pjm-table th { text-align: left; font-size: 12px; color: #6b7280; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
    .pjm-table td { padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; vertical-align: top; }
    .pjm-table tr:last-child td { border-bottom: none; }
    
    /* Inputs & Buttons */
    .pjm-input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 10px; }
    .pjm-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 13px; cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: 0.2s; width: 100%; text-align: center; }
    .pjm-btn-primary { background: #2563eb; color: #fff; }
    .pjm-btn-primary:hover { background: #1d4ed8; }
    .pjm-btn-secondary { background: #fff; border-color: #d1d5db; color: #374151; }
    .pjm-btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
    .pjm-btn-group { display: flex; gap: 8px; }

    /* Timeline */
    .pjm-timeline { position: relative; padding-left: 20px; border-left: 2px solid #e5e7eb; margin-left: 5px; }
    .pjm-timeline-item { position: relative; margin-bottom: 15px; }
    .pjm-timeline-item::before { content: ''; position: absolute; left: -26px; top: 5px; width: 10px; height: 10px; background: #9ca3af; border-radius: 50%; border: 2px solid #fff; }
    .pjm-timeline-date { font-size: 11px; color: #9ca3af; }
    .pjm-timeline-msg { font-size: 13px; color: #4b5563; margin: 0; }

    /* Responsive */
    @media (max-width: 768px) {
        .pjm-grid { flex-direction: column; }
        .pjm-col-main, .pjm-col-side { width: 100%; }
    }
</style>

<div class="pjm-order-view">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; font-size: 24px; color: #111827;">Zamówienie #<?php echo esc_html($order->order_number); ?></h2>
            <span style="color: #6b7280; font-size: 13px;">Złożone: <?php echo esc_html( $order->created_at ); ?></span>
        </div>
        <span class="pjm-badge" style="background-color: <?php echo esc_attr( $status_color ); ?>;">
            <?php echo esc_html( $status_label ); ?>
        </span>
    </div>

    <div class="pjm-grid">
        
        <div class="pjm-col-main">
            
            <div class="pjm-card">
                <div class="pjm-card-header">
                    <h3 class="pjm-card-title"><span class="dashicons dashicons-cart"></span> Szczegóły Zamówienia</h3>
                </div>
                
                <?php if(!empty($order->client_message)): ?>
                    <div style="background: #fffbeb; color: #92400e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fcd34d;">
                        <strong>Uwagi klienta:</strong> <?php echo nl2br(esc_html($order->client_message)); ?>
                    </div>
                <?php endif; ?>

                <table class="pjm-table">
                    <thead>
                        <tr>
                            <th width="50%">Usługa</th>
                            <th width="20%">Szczegóły</th>
                            <th width="15%">Ilość</th>
                            <th width="15%" style="text-align:right;">Cena</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($cart_items)): foreach($cart_items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($item['title'] ?? $item['name'] ?? 'Usługa'); ?></strong>
                                <?php if(!empty($item['_addons_data'])): ?>
                                    <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                                        + <?php echo implode(', ', array_map('esc_html', array_column($item['_addons_data'], 'name'))); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if(!empty($item['meta']) && is_array($item['meta'])) {
                                    $m = $item['meta'];
                                    // Języki zlecenia jako jedna, czytelna para (dwukierunkowa).
                                    if ( ! empty( $m['order_lang_from'] ) || ! empty( $m['order_lang_to'] ) ) {
                                        $pair = function_exists('pjm_lang_pair') ? pjm_lang_pair( $m['order_lang_from'] ?? '', $m['order_lang_to'] ?? '' ) : trim( ($m['order_lang_from'] ?? '').' '.($m['order_lang_to'] ?? '') );
                                        if ( $pair !== '' ) echo '<div style="font-size:11px; color:#6b7280;">Języki: '.esc_html($pair).'</div>';
                                    }
                                    $labels = [ 'dates'=>'Terminy','schedule'=>'Godziny','location'=>'Miejsce','meeting_url'=>'Link online','duration_val'=>'Czas (min)','chars_count'=>'Znaki','mode'=>'Tryb','notes'=>'Uwagi' ];
                                    $skip   = [ 'order_lang_from'=>1, 'order_lang_to'=>1 ];
                                    foreach($m as $k => $v) {
                                        if(isset($skip[$k]) || empty($v) || is_array($v)) continue;
                                        $lbl = $labels[$k] ?? ucfirst($k);
                                        echo '<div style="font-size:11px; color:#6b7280;">'.esc_html($lbl).': '.esc_html($v).'</div>';
                                    }
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($item['quantity'] ?? 1); ?> <?php echo esc_html($item['unit'] ?? 'szt.'); ?></td>
                            <td style="text-align:right; font-weight:600;">
                                <?php echo number_format(floatval($item['pricing']['total'] ?? 0), 2); ?> zł
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4" style="color:#999; text-align:center;">Brak pozycji (błąd danych).</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$is_subscription): ?>
            <div class="pjm-card">
                <div class="pjm-card-header">
                    <h3 class="pjm-card-title"><span class="dashicons dashicons-paperclip"></span> Pliki Źródłowe</h3>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <?php 
                    $has_files = false;
                    if(!empty($files_data) && is_array($files_data)): 
                        foreach($files_data as $key => $link): 
                            if($key === 'deliverable_url' || empty($link) || is_array($link)) continue;
                            $has_files = true;
                    ?>
                        <a href="<?php echo esc_url($link); ?>" target="_blank" class="pjm-btn pjm-btn-secondary" style="width:auto;">
                            <span class="dashicons dashicons-download" style="margin-right:5px;"></span> 
                            Pobierz (<?php echo ucfirst($key); ?>)
                        </a>
                    <?php endforeach; endif; ?>
                    
                    <?php if(!$has_files): ?>
                        <p class="pjm-small">Klient nie załączył plików przy zamówieniu.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$is_subscription && $order->order_status !== 'cancelled'): ?>
            <div class="pjm-card" style="border-color: #bbf7d0;">
                <div class="pjm-card-header" style="background: #f0fdf4; margin: -24px -24px 20px -24px; padding: 15px 24px; border-bottom: 1px solid #bbf7d0; border-radius: 12px 12px 0 0;">
                    <h3 class="pjm-card-title" style="color: #166534;"><span class="dashicons dashicons-upload"></span> Dostarczenie Plików</h3>
                </div>
                
                <p class="pjm-small" style="margin-bottom:10px;">Wklej link do gotowych materiałów (np. WeTransfer, Google Drive). Po kliknięciu "Wyślij", klient otrzyma e-mail z linkiem.</p>
                
                <div style="display:flex; gap:10px;">
                    <input type="url" id="pjm_final_url" class="pjm-input" value="<?php echo esc_url($deliverable_url); ?>" placeholder="https://..." style="margin-bottom:0;">
                    <button class="pjm-btn pjm-btn-primary btn-complete-order" data-id="<?php echo $order->id; ?>" style="width: auto; white-space: nowrap;">
                        Wyślij i Zakończ
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! $is_subscription ) : ?>
            <div class="pjm-card">
                <div class="pjm-card-header">
                    <h3 class="pjm-card-title"><span class="dashicons dashicons-groups"></span> Tłumacze / Przypisania</h3>
                </div>
                <div id="pjm-asg-wrap">
                    <?php
                    $order_id = $order->id;
                    $asg_tpl = PJM_CALC_PATH . 'templates/admin/assignments-section.php';
                    if ( file_exists( $asg_tpl ) ) include $asg_tpl;
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="pjm-card">
                <div class="pjm-card-header">
                    <h3 class="pjm-card-title"><span class="dashicons dashicons-clock"></span> Historia Zdarzeń</h3>
                </div>
                <div class="pjm-timeline" style="max-height: 250px; overflow-y: auto;">
                    <?php if(empty($logs)): ?>
                        <p class="pjm-small">Brak wpisów.</p>
                    <?php else: foreach($logs as $log): ?>
                        <div class="pjm-timeline-item">
                            <div class="pjm-timeline-date"><?php echo esc_html( $log->created_at ); ?> &bull; <?php echo esc_html( $log->created_by ); ?></div>
                            <p class="pjm-timeline-msg"><?php echo esc_html($log->message); ?></p>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>

        <div class="pjm-col-side">
            
            <div class="pjm-card">
                <div class="pjm-card-header">
                    <h3 class="pjm-card-title">Klient</h3>
                </div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                    <div style="width:40px; height:40px; background:#e5e7eb; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#4b5563;">
                        <?php echo strtoupper(substr($user_info->display_name ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <div class="pjm-value"><?php echo $user_info ? esc_html($user_info->display_name) : 'Gość'; ?></div>
                        <a href="mailto:<?php echo esc_attr($user_info->user_email); ?>" class="pjm-small" style="color:#2563eb; text-decoration:none;"><?php echo esc_html($user_info->user_email); ?></a>
                    </div>
                </div>

                <div style="background:#f9fafb; padding:12px; border-radius:6px; border:1px solid #e5e7eb;">
                    <span class="pjm-label">DANE DO FAKTURY</span>
                    <?php if(!empty($invoice_data)): ?>
                        <div class="pjm-value" style="font-size:13px;">
                            <?php if(!empty($invoice_data['company'])): ?>
                                <?php echo esc_html($invoice_data['company']); ?><br>
                                <span style="color:#6b7280;">NIP: <?php echo esc_html($invoice_data['nip']); ?></span>
                            <?php else: ?>
                                <?php echo esc_html($invoice_data['name'] ?? ''); ?>
                            <?php endif; ?>
                            <div style="margin-top:5px; color:#4b5563;">
                                <?php echo esc_html($invoice_data['street'] ?? ''); ?><br>
                                <?php echo esc_html($invoice_data['zip'] ?? '') . ' ' . esc_html($invoice_data['city'] ?? ''); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="pjm-small">Brak danych.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pjm-card">
                <div class="pjm-card-header">
                    <h3 class="pjm-card-title">Termin</h3>
                </div>
                <?php if (!$is_subscription): ?>
                    <label class="pjm-label">Planowany termin oddania</label>
                    <div style="display:flex; gap:5px;">
                        <input type="date" id="pjm_deadline_input" class="pjm-input" style="margin:0;" value="<?php echo $order->deadline ? date('Y-m-d', strtotime($order->deadline)) : ''; ?>">
                        <button class="pjm-btn pjm-btn-secondary btn-save-deadline" data-id="<?php echo $order->id; ?>" style="width:auto;"><span class="dashicons dashicons-saved"></span></button>
                    </div>
                <?php else: ?>
                    <p class="pjm-value" style="color:#10b981;">Subskrypcja (Cykliczne)</p>
                    <button class="pjm-btn pjm-btn-secondary btn-activate-sub" data-id="<?php echo $order->id; ?>" style="margin-top:10px;">Wymuś aktywację</button>
                <?php endif; ?>
            </div>

            <div class="pjm-card" style="background:#f8fafc; border-color:#bfdbfe;">
                <div class="pjm-card-header" style="border-color:#e2e8f0;">
                    <h3 class="pjm-card-title" style="color:#1e40af;">Rozliczenie</h3>
                </div>
                
                <div style="margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span class="pjm-small">Wartość:</span>
                        <span class="pjm-value"><?php echo number_format($subtotal, 2); ?> zł</span>
                    </div>
                    <?php if($discount_value > 0): ?>
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; color:#10b981;">
                        <span class="pjm-small">Rabat:</span>
                        <span class="pjm-value">-<?php echo number_format($discount_value, 2); ?> zł</span>
                    </div>
                    <?php endif; ?>
                    
                    <div style="border-top:1px solid #e2e8f0; margin:10px 0;"></div>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="pjm-value" style="font-weight:700;">RAZEM:</span>
                        <span style="font-size:20px; font-weight:800; color:#1e3a8a;"><?php echo number_format($total_price, 2); ?> zł</span>
                    </div>
                </div>

                <div style="background:#fff; border:1px solid #e5e7eb; padding:10px; border-radius:6px; margin-bottom:15px;">
                    <span class="pjm-label">METODA PŁATNOŚCI</span>
                    <div class="pjm-value"><?php echo esc_html($payment_label); ?></div>
                    <span class="pjm-label" style="margin-top:5px;">STATUS PŁATNOŚCI</span>
                    <div style="font-size:13px; font-weight:600;"><?php echo esc_html( $pay_status_lbl ); ?></div>
                </div>

                <div class="pjm-btn-group">
                    <?php if($order->invoice_url): ?>
                        <a href="<?php echo esc_url($order->invoice_url); ?>" target="_blank" class="pjm-btn pjm-btn-primary">
                            <span class="dashicons dashicons-pdf" style="margin-right:5px;"></span> Pobierz Fakturę
                        </a>
                    <?php else: ?>
                        <button class="pjm-btn pjm-btn-secondary btn-generate-invoice" data-id="<?php echo $order->id; ?>" data-kind="proforma">Proforma</button>
                        <button class="pjm-btn pjm-btn-secondary btn-generate-invoice" data-id="<?php echo $order->id; ?>" data-kind="vat">VAT</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>