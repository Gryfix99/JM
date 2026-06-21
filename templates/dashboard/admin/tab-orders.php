<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Sprawdzenie uprawnień
if ( ! current_user_can( 'manage_options' ) ) {
    echo '<div class="notice notice-error"><p>Brak uprawnień.</p></div>';
    return;
}

global $wpdb;

// --- 1. FILTROWANIE ---
$where_sql = "1=1";
$query_args = [];

// Status
if ( ! empty( $_GET['status_filter'] ) ) {
    $status = sanitize_text_field( $_GET['status_filter'] );
    $where_sql .= " AND o.order_status = %s";
    $query_args[] = $status;
}

// Szukanie (ID, Email, Nazwisko)
if ( ! empty( $_GET['q'] ) ) {
    $q = sanitize_text_field( $_GET['q'] );
    if ( is_numeric( $q ) ) {
        $where_sql .= " AND o.id = %d";
        $query_args[] = $q;
    } else {
        $like = '%' . $wpdb->esc_like( $q ) . '%';
        $where_sql .= " AND (u.user_email LIKE %s OR u.display_name LIKE %s OR o.order_number LIKE %s)";
        $query_args[] = $like;
        $query_args[] = $like;
        $query_args[] = $like;
    }
}

// Pobranie zamówień
$sql = "SELECT o.*, u.display_name, u.user_email 
        FROM {$wpdb->prefix}pjm_orders o 
        LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
        WHERE $where_sql
        ORDER BY o.created_at DESC LIMIT 50";

if ( ! empty( $query_args ) ) {
    $orders = $wpdb->get_results( $wpdb->prepare( $sql, $query_args ) );
} else {
    $orders = $wpdb->get_results( $sql );
}

// Konfiguracja widoku
// Kanoniczny zestaw statusów realizacji (spójny ze słownikiem pjm_order_status_labels()).
$status_map = [
    'new'             => ['label' => 'Nowe',                 'class' => 'gray'],
    'pending_payment' => ['label' => 'Oczekuje na płatność', 'class' => 'orange'],
    'in_progress'     => ['label' => 'W realizacji',         'class' => 'blue'],
    'completed'       => ['label' => 'Zrealizowane',         'class' => 'success'],
    'cancelled'       => ['label' => 'Anulowane',            'class' => 'danger'],
];
// Aliasy starych kodów — tylko do POPRAWNEGO WYŚWIETLENIA istniejących zamówień (nie w dropdownie zmiany).
$status_alias = [
    'draft' => 'new', 'pending' => 'pending_payment', 'payment_pending' => 'pending_payment',
    'processing' => 'in_progress', 'paid' => 'completed',
];

$method_icons = [
    'stripe'      => ['icon' => 'credit_card',            'color' => '#6772e5', 'label' => 'Płatność online'],
    'online'      => ['icon' => 'credit_card',            'color' => '#6772e5', 'label' => 'Płatność online'],
    'wallet_full' => ['icon' => 'account_balance_wallet', 'color' => '#27ae60', 'label' => 'Portfel'],
    'wallet'      => ['icon' => 'account_balance_wallet', 'color' => '#27ae60', 'label' => 'Portfel'],
    'proforma'    => ['icon' => 'description',            'color' => '#7f8c8d', 'label' => 'Proforma'],
    'faktura_po'  => ['icon' => 'request_quote',          'color' => '#a16207', 'label' => 'Faktura przelewowa'],
    'transfer'    => ['icon' => 'account_balance',        'color' => '#7f8c8d', 'label' => 'Przelew'],
    'invoice'     => ['icon' => 'receipt',                'color' => '#95a5a6', 'label' => 'Faktura'],
    'cash'        => ['icon' => 'payments',               'color' => '#27ae60', 'label' => 'Gotówka'],
    ''            => ['icon' => 'help_outline',           'color' => '#ccc',    'label' => '-']
];

/* ============================================================
 * SKRZYNKA „WYMAGA UWAGI" — buckety operacyjne dla admina.
 * Każdy bucket to lista zamówień klikalna w istniejący modal
 * szczegółów (.btn-details data-id → pjm_admin_get_details).
 * ============================================================ */
$O = "{$wpdb->prefix}pjm_orders";
$A = "{$wpdb->prefix}pjm_order_assignments";
$T = "{$wpdb->prefix}pjm_translators";
$U = $wpdb->users;

// Statusy „w toku" (realizacja jeszcze trwa) — z aliasami starych kodów.
$active_in = "'new','pending_payment','in_progress','processing','payment_pending','pending','draft'";
$done_in   = "'completed','paid'";

// Wspólny zestaw kolumn zamówienia.
$ocols = "o.id, o.order_number, o.total_price, o.created_at, o.order_json, o.order_status, o.payment_status";

// 1) NIEPRZYPISANE — aktywne, nie-subskrypcja, bez żadnego (nieanulowanego/nieodrzuconego) przypisania i bez odrzucenia.
$att_unassigned = $wpdb->get_results("
    SELECT {$ocols}, u.display_name, u.user_email
    FROM {$O} o LEFT JOIN {$U} u ON o.user_id = u.ID
    WHERE o.order_status IN ({$active_in})
      AND (o.package_id IS NULL OR o.package_id NOT LIKE '%subscription%')
      AND NOT EXISTS (SELECT 1 FROM {$A} a  WHERE a.order_id  = o.id AND a.assignment_status NOT IN ('cancelled','declined'))
      AND NOT EXISTS (SELECT 1 FROM {$A} a2 WHERE a2.order_id = o.id AND a2.assignment_status = 'declined')
    ORDER BY o.created_at DESC LIMIT 50
");

// 2) ODRZUCONE PRZEZ TŁUMACZA — aktywne, mają odrzucenie i nadal brak aktywnego przypisania (zostały „odkryte").
$att_declined = $wpdb->get_results("
    SELECT {$ocols}, u.display_name, u.user_email, t.display_name AS tr_name, MAX(a.updated_at) AS last_decline
    FROM {$A} a
    INNER JOIN {$O} o ON a.order_id = o.id
    LEFT JOIN {$U} u ON o.user_id = u.ID
    LEFT JOIN {$T} t ON a.translator_id = t.id
    WHERE a.assignment_status = 'declined'
      AND o.order_status IN ({$active_in})
      AND NOT EXISTS (SELECT 1 FROM {$A} a3 WHERE a3.order_id = o.id AND a3.assignment_status NOT IN ('cancelled','declined'))
    GROUP BY o.id
    ORDER BY last_decline DESC LIMIT 50
");

// 3) CZEKA NA POTWIERDZENIE — brief wysłany, tłumacz jeszcze nie zaakceptował (najdłużej czekające u góry).
$att_awaiting = $wpdb->get_results("
    SELECT {$ocols}, u.display_name, u.user_email, t.display_name AS tr_name, a.work_date, a.sent_at
    FROM {$A} a
    INNER JOIN {$O} o ON a.order_id = o.id
    LEFT JOIN {$U} u ON o.user_id = u.ID
    LEFT JOIN {$T} t ON a.translator_id = t.id
    WHERE a.assignment_status = 'sent'
    ORDER BY a.sent_at ASC LIMIT 50
");

// 4) DO ZAFAKTUROWANIA — zrealizowane, brak linku do faktury, nie-subskrypcja.
$att_invoice = $wpdb->get_results("
    SELECT {$ocols}, u.display_name, u.user_email
    FROM {$O} o LEFT JOIN {$U} u ON o.user_id = u.ID
    WHERE o.order_status IN ({$done_in})
      AND (o.invoice_url IS NULL OR o.invoice_url = '')
      AND (o.package_id IS NULL OR o.package_id NOT LIKE '%subscription%')
    ORDER BY o.created_at DESC LIMIT 50
");

// 5) PŁATNOŚĆ DO PILNOWANIA — po terminie lub odroczona po realizacji (faktura przelewowa).
$att_payment = $wpdb->get_results("
    SELECT {$ocols}, u.display_name, u.user_email
    FROM {$O} o LEFT JOIN {$U} u ON o.user_id = u.ID
    WHERE (o.payment_status = 'overdue'
           OR (o.payment_status = 'deferred' AND o.order_status IN ('completed','paid','in_progress')))
    ORDER BY o.created_at DESC LIMIT 50
");

// Definicja bucketów (kolejność = priorytet).
$att_buckets = [
    'unassigned' => ['label' => 'Nieprzypisane',        'icon' => 'person_off',      'color' => '#c62828', 'rows' => $att_unassigned, 'desc' => 'Brak tłumacza'],
    'declined'   => ['label' => 'Odrzucone',            'icon' => 'cancel',          'color' => '#e8590c', 'rows' => $att_declined,   'desc' => 'Tłumacz odmówił — przydziel ponownie'],
    'awaiting'   => ['label' => 'Czeka na potwierdz.',  'icon' => 'hourglass_top',   'color' => '#a16207', 'rows' => $att_awaiting,   'desc' => 'Brief wysłany, brak akceptacji'],
    'invoice'    => ['label' => 'Do zafakturowania',    'icon' => 'request_quote',   'color' => '#1c7ed6', 'rows' => $att_invoice,    'desc' => 'Zrealizowane bez faktury'],
    'payment'    => ['label' => 'Płatność',             'icon' => 'payments',        'color' => '#2f9e44', 'rows' => $att_payment,    'desc' => 'Po terminie / odroczona'],
];
$att_total = 0;
foreach ( $att_buckets as $b ) { $att_total += count( $b['rows'] ); }

// Pierwszy niepusty bucket — otwarty domyślnie.
$att_open = '';
foreach ( $att_buckets as $bk => $b ) { if ( ! empty( $b['rows'] ) ) { $att_open = $bk; break; } }

// Tytuł zamówienia z order_json (jak w tabeli niżej).
$att_title = function( $json ) {
    $items = json_decode( (string) $json, true );
    if ( ! empty( $items ) && is_array( $items ) ) {
        $first = reset( $items );
        $t = $first['title'] ?? $first['name'] ?? ( $first['service_type'] ?? 'Zamówienie' );
        return mb_strimwidth( (string) $t, 0, 40, '…' );
    }
    return 'Zamówienie';
};
?>

<style>
    .ja-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-top: 20px; }
    .ja-table { width: 100%; border-collapse: collapse; text-align: left; }
    .ja-table th { padding: 15px; background: #f8f9fa; border-bottom: 2px solid #eee; font-size: 12px; text-transform: uppercase; color: #555; }
    .ja-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .ja-table tr:hover { background: #fcfcfc; }
    
    /* Status Badges */
    .ja-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; white-space: nowrap; position: relative; }
    .ja-badge.gray { background: #e9ecef; color: #495057; }
    .ja-badge.blue { background: #e7f5ff; color: #1c7ed6; }
    .ja-badge.warning { background: #fff9db; color: #f08c00; }
    .ja-badge.orange { background: #fff4e6; color: #e8590c; }
    .ja-badge.success { background: #ebfbee; color: #2f9e44; }
    .ja-badge.danger { background: #fff5f5; color: #fa5252; }

    /* Action Buttons */
    .ja-icon-btn { border: none; background: #f1f3f5; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #555; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; margin-left: 4px; }
    .ja-icon-btn:hover { background: #e9ecef; color: #228be6; }
    .ja-icon-btn.active { background: #e7f5ff; color: #1c7ed6; }
    .ja-icon-btn span { font-size: 18px; }

    .ja-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
    .user-info small { display: block; color: #888; }

    /* === Skrzynka „Wymaga uwagi" === */
    .pjm-att { background:#fff; border:1px solid #e3e8e5; border-radius:12px; box-shadow:0 2px 6px rgba(20,53,42,.05); margin-top:20px; overflow:hidden; }
    .pjm-att-head { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid #eef2f0; }
    .pjm-att-head h3 { margin:0; font-size:15px; color:#14352A; display:flex; align-items:center; gap:8px; }
    .pjm-att-head .material-symbols-rounded { font-size:20px; color:#E0A33E; }
    .pjm-att-total { margin-left:auto; font-size:12px; color:#5f6b65; }
    .pjm-att-total b { color:#14352A; }
    .pjm-att-allclear { display:flex; align-items:center; gap:10px; padding:22px 20px; color:#2f9e44; font-size:14px; }
    .pjm-att-allclear .material-symbols-rounded { font-size:22px; }

    .pjm-att-chips { display:flex; gap:10px; padding:14px 20px 4px; flex-wrap:wrap; }
    .pjm-chip { display:flex; align-items:center; gap:9px; padding:9px 13px; border:1px solid #e3e8e5; border-radius:10px; background:#fbfcfc; cursor:pointer; transition:.15s; font:inherit; text-align:left; min-width:0; }
    .pjm-chip:hover:not([disabled]) { border-color:#c9d6cf; box-shadow:0 1px 3px rgba(20,53,42,.08); }
    .pjm-chip[disabled] { opacity:.5; cursor:default; }
    .pjm-chip.active { border-color:var(--c,#14352A); background:#fff; box-shadow:inset 0 0 0 1px var(--c,#14352A); }
    .pjm-chip:focus-visible, .pjm-att-item:focus-visible { outline:3px solid #E0A33E; outline-offset:2px; }
    .pjm-chip-ico { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex:none; color:#fff; }
    .pjm-chip-ico .material-symbols-rounded { font-size:18px; }
    .pjm-chip-txt { line-height:1.15; }
    .pjm-chip-n { font-size:17px; font-weight:800; color:#16241D; }
    .pjm-chip-l { font-size:11px; color:#5f6b65; white-space:nowrap; }

    .pjm-att-lists { padding:6px 20px 18px; }
    .pjm-att-list { display:none; }
    .pjm-att-list.open { display:block; }
    .pjm-att-desc { font-size:12px; color:#8a948f; margin:6px 0 10px; }
    .pjm-att-item { display:flex; align-items:center; gap:12px; width:100%; text-align:left; padding:11px 12px; border:1px solid #eef2f0; border-radius:9px; background:#fff; cursor:pointer; margin-bottom:7px; font:inherit; transition:.15s; }
    .pjm-att-item:hover { border-color:#cfe0d7; background:#fafdfb; }
    .pjm-att-item .ai-id { font-weight:800; color:#14352A; font-size:13px; flex:none; width:54px; }
    .pjm-att-item .ai-main { flex:1; min-width:0; line-height:1.3; }
    .pjm-att-item .ai-title { font-weight:600; color:#1f2a25; font-size:13px; }
    .pjm-att-item .ai-sub { font-size:11.5px; color:#5f6b65; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pjm-att-item .ai-meta { flex:none; text-align:right; font-size:11.5px; color:#5f6b65; }
    .pjm-att-item .ai-meta b { display:block; color:#16241D; font-size:13px; }
    .pjm-att-item .ai-go { flex:none; color:#9aa6a0; }
    .pjm-att-item .ai-go .material-symbols-rounded { font-size:20px; }
    @media (max-width:600px){
        .pjm-att-item .ai-meta { display:none; }
        .pjm-chip-l { white-space:normal; }
    }
</style>

<?php
// Renderer pojedynczego elementu skrzynki — klikalny w modal szczegółów.
$att_render_item = function( $row, $bucket ) use ( $att_title ) {
    $price = number_format( (float) $row->total_price, 2, ',', ' ' );
    $client = $row->display_name ?: ( $row->user_email ?: 'Gość' );
    $sub = '';
    if ( $bucket === 'awaiting' || $bucket === 'declined' ) {
        $tr = isset( $row->tr_name ) ? $row->tr_name : '';
        $sub = $client . ( $tr ? ' · ' . $tr : '' );
    } else {
        $sub = $client;
    }
    $meta_line = '';
    if ( $bucket === 'awaiting' && ! empty( $row->sent_at ) ) {
        $days = floor( ( time() - strtotime( $row->sent_at ) ) / 86400 );
        $meta_line = $days <= 0 ? 'dziś' : $days . ' dni temu';
    } elseif ( ! empty( $row->created_at ) ) {
        $meta_line = date_i18n( 'd.m', strtotime( $row->created_at ) );
    }
    ?>
    <button type="button" class="pjm-att-item btn-details" data-id="<?php echo (int) $row->id; ?>" title="Otwórz szczegóły / przypisz">
        <span class="ai-id">#<?php echo (int) $row->id; ?></span>
        <span class="ai-main">
            <span class="ai-title"><?php echo esc_html( $att_title( $row->order_json ) ); ?></span>
            <span class="ai-sub"><?php echo esc_html( $sub ); ?></span>
        </span>
        <span class="ai-meta"><b><?php echo esc_html( $price ); ?> zł</b><?php echo $meta_line ? esc_html( $meta_line ) : ''; ?></span>
        <span class="ai-go"><span class="material-symbols-rounded">chevron_right</span></span>
    </button>
    <?php
};
?>

<div class="pjm-att" id="pjm-att-box">
    <div class="pjm-att-head">
        <h3><span class="material-symbols-rounded">notifications_active</span> Wymaga uwagi</h3>
        <span class="pjm-att-total"><b><?php echo (int) $att_total; ?></b> <?php echo ( $att_total === 1 ? 'pozycja' : ( $att_total >= 2 && $att_total <= 4 ? 'pozycje' : 'pozycji' ) ); ?></span>
    </div>

    <?php if ( $att_total === 0 ) : ?>
        <div class="pjm-att-allclear">
            <span class="material-symbols-rounded">task_alt</span>
            Brak pozycji wymagających działania.
        </div>
    <?php else : ?>
        <div class="pjm-att-chips" role="tablist" aria-label="Kategorie wymagające uwagi">
            <?php foreach ( $att_buckets as $bk => $b ) :
                $n = count( $b['rows'] );
                $is_open = ( $bk === $att_open );
            ?>
            <button type="button" role="tab" id="pjm-att-tab-<?php echo esc_attr( $bk ); ?>"
                    aria-controls="pjm-att-panel-<?php echo esc_attr( $bk ); ?>"
                    aria-selected="<?php echo $is_open ? 'true' : 'false'; ?>"
                    tabindex="<?php echo $is_open ? '0' : '-1'; ?>"
                    class="pjm-chip<?php echo $is_open ? ' active' : ''; ?>" data-bucket="<?php echo esc_attr( $bk ); ?>"
                    style="--c:<?php echo esc_attr( $b['color'] ); ?>;" <?php echo $n === 0 ? 'disabled' : ''; ?>>
                <span class="pjm-chip-ico" style="background:<?php echo esc_attr( $b['color'] ); ?>;">
                    <span class="material-symbols-rounded"><?php echo esc_attr( $b['icon'] ); ?></span>
                </span>
                <span class="pjm-chip-txt">
                    <span class="pjm-chip-n"><?php echo $n; ?></span>
                    <span class="pjm-chip-l"><?php echo esc_html( $b['label'] ); ?></span>
                </span>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="pjm-att-lists">
            <?php foreach ( $att_buckets as $bk => $b ) : ?>
            <div class="pjm-att-list<?php echo ( $bk === $att_open ) ? ' open' : ''; ?>" data-bucket="<?php echo esc_attr( $bk ); ?>"
                 role="tabpanel" id="pjm-att-panel-<?php echo esc_attr( $bk ); ?>" aria-labelledby="pjm-att-tab-<?php echo esc_attr( $bk ); ?>" tabindex="0">
                <?php
                if ( empty( $b['rows'] ) ) {
                    echo '<p style="color:#9aa6a0; font-size:13px; padding:6px 0;">Brak pozycji.</p>';
                } else {
                    foreach ( $b['rows'] as $row ) { $att_render_item( $row, $bk ); }
                }
                ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(function($){
    var $box = $('#pjm-att-box');
    function activate($chip, focus){
        if (!$chip.length || $chip.is('[disabled]')) return;
        var bk = $chip.data('bucket');
        $box.find('.pjm-chip').removeClass('active').attr({ 'aria-selected':'false', tabindex:'-1' });
        $chip.addClass('active').attr({ 'aria-selected':'true', tabindex:'0' });
        $box.find('.pjm-att-list').removeClass('open').filter('[data-bucket="'+bk+'"]').addClass('open');
        if (focus) $chip.trigger('focus');
    }
    $box.on('click', '.pjm-chip:not([disabled])', function(){ activate($(this), false); });
    // Klawiatura: strzałki / Home / End między zakładkami (wzorzec WAI-ARIA Tabs).
    $box.on('keydown', '.pjm-chip', function(e){
        var $tabs = $box.find('.pjm-chip').not('[disabled]');
        var i = $tabs.index(this); if (i < 0) return;
        var ni = null;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') ni = (i + 1) % $tabs.length;
        else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') ni = (i - 1 + $tabs.length) % $tabs.length;
        else if (e.key === 'Home') ni = 0;
        else if (e.key === 'End') ni = $tabs.length - 1;
        if (ni !== null){ e.preventDefault(); activate($tabs.eq(ni), true); }
    });
});
</script>

<div class="ja-card table-card">
    
    <div class="ja-toolbar" style="padding: 20px; border-bottom: 1px solid #eee; display: flex; gap: 15px; flex-wrap: wrap;">
        <form method="GET" style="display:flex; gap:10px; width:100%; align-items:center;">
            <input type="hidden" name="page" value="pjm-orders">
            
            <div class="ja-input-group" style="position:relative; flex:1; max-width:300px;">
                <span class="dashicons dashicons-search" style="position:absolute; left:8px; top:50%; transform:translateY(-50%); color:#999;"></span>
                <input type="text" name="q" value="<?php echo esc_attr($_GET['q'] ?? ''); ?>" placeholder="Szukaj (ID, email, nazwisko)..." class="ja-input" style="padding-left:30px; width: 100%;">
            </div>

            <select name="status_filter" class="ja-input" onchange="this.form.submit()" style="width:150px;">
                <option value="">Status: Wszystkie</option>
                <?php foreach($status_map as $k => $v): ?>
                    <option value="<?php echo $k; ?>" <?php selected($_GET['status_filter'] ?? '', $k); ?>>
                        <?php echo $v['label']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if(!empty($_GET['q']) || !empty($_GET['status_filter'])): ?>
                <a href="?page=pjm-orders" class="button">Reset</a>
            <?php endif; ?>
            
            <div style="margin-left:auto;">
                <span style="color:#999; font-size:12px;">Wyświetlono: <?php echo count($orders); ?></span>
            </div>
        </form>
    </div>

    <div id="ja-bulk-bar" style="display:none; padding:12px 20px; background:#eef5f1; border-bottom:1px solid #d7e6df; align-items:center; gap:12px; flex-wrap:wrap;">
        <strong id="ja-bulk-count" style="color:#16241D;">Zaznaczono: 0</strong>
        <select id="ja-bulk-status" class="ja-input" style="width:180px;">
            <option value="">— Zmień status na… —</option>
            <?php foreach($status_map as $k=>$v): ?>
                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v['label']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" id="ja-bulk-apply-status" class="button">Zastosuj status</button>
        <button type="button" id="ja-bulk-delete" class="button" style="color:#c62828; border-color:#f0c0c0;">Usuń zaznaczone</button>
        <button type="button" id="ja-bulk-clear" class="button" style="margin-left:auto;">Wyczyść</button>
    </div>

    <div class="table-responsive">
        <table class="ja-table">
            <thead>
                <tr>
                    <th width="36"><input type="checkbox" id="ja-select-all" title="Zaznacz wszystkie"></th>
                    <th width="80">ID / Data</th>
                    <th>Klient</th>
                    <th>Usługa (Koszyk)</th>
                    <th>Płatność</th>
                    <th>Status</th>
                    <th style="text-align:right;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($orders)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:30px; color:#777;">Brak zamówień spełniających kryteria.</td></tr>
                <?php endif; ?>

                <?php foreach($orders as $o): 
                    // 1. Parsowanie JSONów
                    $cart_items = json_decode($o->order_json, true);
                    $files_data = json_decode($o->files_json, true);
                    
                    // 2. Wyciąganie Tytułu
                    $title = 'Błąd danych';
                    $count_more = 0;
                    
                    if (!empty($cart_items) && is_array($cart_items)) {
                        $first = reset($cart_items);
                        $title = $first['title'] ?? $first['name'] ?? ($first['service_type'] ?? 'Zamówienie');
                        if (count($cart_items) > 1) $count_more = count($cart_items) - 1;
                    } elseif (strpos($o->package_id, 'subscription') !== false) {
                        $title = 'Subskrypcja: ' . ucfirst(str_replace('subscription_', '', $o->package_id));
                    }

                    // 3. Status i Metoda — stare kody mapujemy na kanoniczne (alias), żeby badge/select pasowały.
                    $st_key = $status_alias[$o->order_status] ?? $o->order_status;
                    $st_info = $status_map[$st_key] ?? ['label' => (function_exists('pjm_order_status_label') ? pjm_order_status_label($o->order_status) : $o->order_status), 'class' => 'gray'];
                    
                    $pay_key = $o->payment_method;
                    if (empty($pay_key) && $o->order_status == 'completed') $pay_key = 'invoice';
                    $pay_info = $method_icons[$pay_key] ?? $method_icons[''];

                    // 4. Plik i Data
                    $is_delivered = !empty($files_data['deliverable_url']);
                    $date_str = date_i18n('d.m.Y', strtotime($o->created_at));
                ?>
                <tr class="row-hover" data-id="<?php echo $o->id; ?>">
                    <td><input type="checkbox" class="ja-row-check" value="<?php echo $o->id; ?>"></td>
                    <td>
                        <div style="line-height:1.2;">
                            <strong style="color:#333;">#<?php echo $o->id; ?></strong><br>
                            <span style="font-size:11px; color:#999;"><?php echo $date_str; ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="user-info">
                            <strong><?php echo esc_html($o->display_name ?: 'Gość'); ?></strong>
                            <small><?php echo esc_html($o->user_email); ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="service-cell">
                            <strong><?php echo esc_html(mb_strimwidth($title, 0, 45, '...')); ?></strong>
                            <?php if($count_more > 0): ?>
                                <span style="font-size:10px; background:#eee; padding:2px 5px; border-radius:10px; color:#666;">+<?php echo $count_more; ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="price-cell">
                            <span style="font-weight:700; color:#333;"><?php echo number_format($o->total_price, 2, ',', ' '); ?> zł</span>
                            <div style="display:flex; align-items:center; gap:5px; font-size:11px; color:<?php echo $pay_info['color']; ?>; margin-top:2px;">
                                <span class="dashicons <?php echo str_replace('dashicons-', '', str_replace('_outline', '', $pay_info['icon'])); ?>" style="font-size:14px; width:14px; height:14px;"></span>
                                <span><?php echo $pay_info['label']; ?></span>
                            </div>
                        </div>
                    </td>
                    
                    <td>
                        <div style="position:relative; display:inline-block;">
                            
                            <span class="ja-badge <?php echo esc_attr($st_info['class']); ?>" style="padding-right:20px; cursor:pointer;">
                                <span class="ja-badge-label"><?php echo esc_html($st_info['label']); ?></span>
                                <span class="dashicons dashicons-arrow-down-alt2" style="font-size:12px; position:absolute; right:4px; top:50%; transform:translateY(-50%); opacity:0.7;"></span>
                            </span>

                            <select class="ja-status-changer" data-id="<?php echo $o->id; ?>"
                                    style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; margin:0; cursor:pointer; z-index:10;">
                                <?php foreach($status_map as $key => $val): ?>
                                    <option value="<?php echo esc_attr($key); ?>" data-class="<?php echo esc_attr($val['class']); ?>" <?php selected($st_key, $key); ?>>
                                        <?php echo esc_html($val['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                        </div>
                    </td>

                    <td style="text-align:right;">
                        <div class="actions-cell">
                            <button class="ja-icon-btn btn-details" data-id="<?php echo $o->id; ?>" title="Szczegóły, pliki, tłumacze">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>

                            <?php if($o->invoice_url): ?>
                                <a href="<?php echo esc_url($o->invoice_url); ?>" target="_blank" class="ja-icon-btn active" title="Pobierz Fakturę">
                                    <span class="dashicons dashicons-media-document"></span>
                                </a>
                            <?php endif; ?>

                            <?php if($is_delivered): ?>
                                <span class="ja-icon-btn" title="Pliki dostarczone" style="color:#27ae60; cursor:default;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </span>
                            <?php endif; ?>

                            <button class="ja-icon-btn btn-delete-order" data-id="<?php echo $o->id; ?>" data-number="<?php echo esc_attr($o->order_number); ?>" title="Usuń zamówienie" style="color:#c62828;">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php // Obsługa statusu i modala: assets/js/admin-dashboard.js (delegowane, pjm_admin_vars.ajax_url) ?>