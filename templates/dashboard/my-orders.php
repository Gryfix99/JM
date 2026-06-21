<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 

// 1. Pobieranie zamówień użytkownika
global $wpdb;
$user_id = get_current_user_id();
$table_name = $wpdb->prefix . 'pjm_orders';

// Bezpieczne sprawdzenie czy tabela istnieje
if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    echo '<div style="padding:20px; color:red;">Tabela zamówień nie istnieje. Skontaktuj się z administratorem.</div>';
    return;
}

// Pobieramy 50 ostatnich zamówień
$orders = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
    $user_id
) );

// 2. Helper: Mapowanie danych do widoku (Funkcja anonimowa lub globalna - tutaj inline dla prostoty szablonu)
if ( ! function_exists( 'pjm_get_order_view_data' ) ) {
    function pjm_get_order_view_data( $order ) {
        // A. Statusy
        $statuses = [
            'draft'           => ['label' => 'Szkic / Nieopłacone', 'color' => '#757575', 'bg' => '#f5f5f5', 'border' => '#bdbdbd'],
            'new'             => ['label' => 'Nowe',                'color' => '#1565c0', 'bg' => '#e3f2fd', 'border' => '#bbdefb'],
            'pending'         => ['label' => 'Oczekuje na płatność','color' => '#ef6c00', 'bg' => '#FBF1DC', 'border' => '#ffe0b2'],
            'pending_payment' => ['label' => 'Oczekuje na płatność','color' => '#ef6c00', 'bg' => '#FBF1DC', 'border' => '#ffe0b2'],
            'payment_pending' => ['label' => 'Oczekuje na płatność','color' => '#ef6c00', 'bg' => '#FBF1DC', 'border' => '#ffe0b2'],
            'in_progress'     => ['label' => 'W realizacji',        'color' => '#0277bd', 'bg' => '#e1f5fe', 'border' => '#b3e5fc'],
            'processing'      => ['label' => 'W realizacji',        'color' => '#0277bd', 'bg' => '#e1f5fe', 'border' => '#b3e5fc'],
            'completed'       => ['label' => 'Zrealizowane',        'color' => '#2e7d32', 'bg' => '#e8f5e9', 'border' => '#c8e6c9'],
            'paid'            => ['label' => 'Opłacone',            'color' => '#2e7d32', 'bg' => '#e8f5e9', 'border' => '#c8e6c9'],
            'cancelled'       => ['label' => 'Anulowane',           'color' => '#c62828', 'bg' => '#ffebee', 'border' => '#ffcdd2'],
        ];

        $status_key = $order->order_status ?? 'new';
        $status_data = $statuses[$status_key] ?? $statuses['new'];

        // B. Typ usługi
        $pkg_id = (string)($order->package_id ?? ''); // Rzutowanie na string dla bezpieczeństwa
        $mode   = $order->delivery_mode ?? '';
        
        // Domyślna wartość
        $type_data = ['label' => 'Usługa PJM', 'icon' => 'inventory_2']; 

        // Rozpoznawanie
        if ( strpos( $pkg_id, 'subscription' ) !== false || in_array( $mode, ['monthly', 'yearly'] ) ) {
            $type_data = ['label' => 'Abonament PJM', 'icon' => 'diamond'];
        } elseif ( strpos( $pkg_id, 'video' ) !== false || strpos( $pkg_id, 'film' ) !== false ) {
            $type_data = ['label' => 'Tłumaczenie Wideo', 'icon' => 'videocam'];
        } elseif ( strpos( $pkg_id, 'text' ) !== false ) {
            $type_data = ['label' => 'Tłumaczenie Tekstu', 'icon' => 'description'];
        } elseif ( strpos( $pkg_id, 'event' ) !== false || strpos( $pkg_id, 'live' ) !== false || strpos( $pkg_id, 'online' ) !== false ) {
            $type_data = ['label' => 'Obsługa Wydarzenia', 'icon' => 'calendar_month'];
        } elseif ( strpos( $pkg_id, 'loop' ) !== false ) {
            $type_data = ['label' => 'Pętla Indukcyjna', 'icon' => 'hearing'];
        }

        // C. Dekodowanie plików
        $files_data = json_decode($order->files_json ?? '{}', true) ?: [];
        $deliverable = $files_data['deliverable_url'] ?? '';

        return [
            'status'      => $status_data,
            'type'        => $type_data,
            'invoice_url' => $order->invoice_url ?? '',
            'deliverable' => $deliverable
        ];
    }
}
?>

<div class="pjm-dashboard-content">
    
    <div class="header-flex" style="margin-bottom: 30px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:15px;">
        <div class="pjm-section-header" style="margin-bottom:0;">
            <h2 style="margin:0;">Historia Zamówień</h2>
            
        </div>
        
        <div class="pjm-dropdown-wrapper" style="position:relative;">
            <a href="?tab=calculator" class="ja-btn btn-primary" style="display:flex; align-items:center; gap:8px; padding:10px 20px; text-decoration:none; background:#1B5E4B; color:#fff; border-radius:6px; font-weight:600;">
                <span class="material-symbols-rounded">add</span>
                Nowe zamówienie
            </a>
        </div>
    </div>

    <?php if ( empty( $orders ) ) : ?>
        
        <div class="pjm-empty-state" style="text-align:center; padding:50px 20px; background:#f9f9f9; border-radius:12px;">
            <span class="material-symbols-rounded" style="font-size:48px; color:#ccc;">inbox</span>
            <h3 style="color:#444; margin-top:15px;">Brak zamówień</h3>
            <p style="color:#777;">Nie złożyłeś jeszcze żadnego zamówienia. Skorzystaj z kalkulatora.</p>
            <a href="?tab=calculator" class="ja-btn" style="margin-top:15px; display:inline-block; padding:10px 20px; background:#fff; border:1px solid #ddd; text-decoration:none; color:#333; border-radius:6px;">Rozpocznij Wycenę</a>
        </div>

    <?php else : ?>
        
        <div class="pjm-table-wrapper" style="background:#fff; border-radius:12px; border:1px solid #eee; overflow-x:auto;">
            <table class="pjm-table" style="width:100%; border-collapse:collapse; min-width:600px;">
                <thead style="background:#f8f9fa; border-bottom:1px solid #eee;">
                    <tr>
                        <th style="padding:15px; text-align:left; font-size:12px; text-transform:uppercase; color:#888;">Nr Zamówienia</th>
                        <th style="padding:15px; text-align:left; font-size:12px; text-transform:uppercase; color:#888;">Usługa</th>
                        <th style="padding:15px; text-align:left; font-size:12px; text-transform:uppercase; color:#888;">Kwota</th>
                        <th style="padding:15px; text-align:left; font-size:12px; text-transform:uppercase; color:#888;">Status</th>
                        <th style="padding:15px; text-align:right; font-size:12px; text-transform:uppercase; color:#888;">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $orders as $order ) : 
                        $view = pjm_get_order_view_data( $order );
                        $date = date( 'd.m.Y', strtotime( $order->created_at ) );
                        $is_express = ($order->delivery_mode === 'exp24' || $order->delivery_mode === 'exp48');
                        $pkg_name = ucfirst(str_replace(['_', 'subscription'], [' ', ''], (string)($order->package_id ?? '')));
                    ?>
                    <tr style="border-bottom:1px solid #f1f1f1;">
                        
                        <td style="padding:15px;">
                            <div style="display:flex; flex-direction:column;">
                                <strong style="color:#333; font-family:monospace; font-size:14px;">
                                    <?php echo esc_html( $order->order_number ?: '#' . $order->id ); ?>
                                </strong>
                                <span style="font-size:12px; color:#999; margin-top:3px;"><?php echo $date; ?></span>
                            </div>
                        </td>

                        <td style="padding:15px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="background:#FBF1DC; padding:8px; border-radius:50%; display:flex; align-items:center; justify-content:center; width:36px; height:36px;">
                                    <span class="material-symbols-rounded" style="color:#1B5E4B; font-size:20px;">
                                        <?php echo $view['type']['icon']; ?>
                                    </span>
                                </div>
                                <div style="display:flex; flex-direction:column;">
                                    <span style="font-weight:500; color:#333; font-size:14px;"><?php echo esc_html( $view['type']['label'] ); ?></span>
                                    <span style="font-size:12px; color:#999;">
                                        <?php echo $pkg_name; ?>
                                        <?php if($is_express): ?>
                                            <span style="color:#c62828; font-weight:700; margin-left:5px;">EXP</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td style="padding:15px;">
                            <strong style="color:#333; font-size:14px;"><?php echo number_format( (float)$order->total_price, 2, ',', ' ' ); ?> zł</strong>
                        </td>

                        <td style="padding:15px;">
                            <?php 
                                $s = $view['status'];
                                $style = "background:{$s['bg']}; color:{$s['color']}; border:1px solid {$s['border']};";
                            ?>
                            <span style="display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; <?php echo $style; ?>">
                                <?php echo esc_html( $s['label'] ); ?>
                            </span>
                        </td>

                        <td style="padding:15px; text-align:right;">
                            <div class="pjm-actions-cell" style="display:flex; justify-content:flex-end; gap:8px; align-items:center;">
                                
                                <?php if ( in_array($order->order_status, ['draft', 'pending', 'new']) ) : ?>
                                    <a href="?tab=checkout&order_id=<?php echo $order->id; ?>" class="btn-sm" style="background:#333; color:#fff; padding:6px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:600;">
                                        Dokończ / Opłać
                                    </a>
                                <?php endif; ?>

                                <?php if ( !empty($view['invoice_url']) ): ?>
                                    <?php 
                                        $is_proforma = in_array($order->order_status, ['payment_pending', 'pending_payment']);
                                        $btn_color = $is_proforma ? '#ef6c00' : '#777';
                                        $btn_bg    = $is_proforma ? '#FBF1DC' : '#f5f5f5';
                                        $btn_title = $is_proforma ? 'Pobierz Proformę' : 'Pobierz Fakturę';
                                        $icon      = $is_proforma ? 'description' : 'receipt_long';
                                    ?>
                                    <a href="<?php echo esc_url( $view['invoice_url'] ); ?>" target="_blank" title="<?php echo $btn_title; ?>" style="color:<?php echo $btn_color; ?>; background:<?php echo $btn_bg; ?>; padding:6px; border-radius:5px; display:inline-flex; text-decoration:none; border:1px solid rgba(0,0,0,0.05);">
                                        <span class="material-symbols-rounded" style="font-size:18px;"><?php echo $icon; ?></span>
                                    </a>
                                <?php endif; ?>

                                <?php if ( ($order->order_status === 'completed' || $order->order_status === 'paid') && !empty($view['deliverable']) ) : ?>
                                    <a href="<?php echo esc_url($view['deliverable']); ?>" target="_blank" title="Pobierz gotowe materiały" style="color:#fff; background:#2e7d32; padding:6px 10px; border-radius:5px; display:inline-flex; text-decoration:none; align-items:center; gap:5px; font-size:12px; font-weight:600;">
                                        <span class="material-symbols-rounded" style="font-size:18px;">cloud_download</span>
                                        Pobierz
                                    </a>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>