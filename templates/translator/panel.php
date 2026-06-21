<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Panel zalogowanego tłumacza. Oczekuje $translator (array).
 */
global $wpdb;
$tid = (int) $translator['id'];

$assignments = $wpdb->get_results( $wpdb->prepare(
    "SELECT a.*, o.order_number, o.deadline, o.order_status, o.order_json
     FROM {$wpdb->prefix}pjm_order_assignments a
     LEFT JOIN {$wpdb->prefix}pjm_orders o ON a.order_id = o.id
     WHERE a.translator_id = %d
     ORDER BY COALESCE(o.deadline, a.created_at) DESC LIMIT 100",
    $tid
) );

// „Z kim" — współpracownicy na tych samych zleceniach (jedno zapytanie).
$cowork = [];
$oids = array_values( array_unique( array_map( function( $a ) { return (int) $a->order_id; }, $assignments ) ) );
if ( $oids ) {
    $ph = implode( ',', array_fill( 0, count( $oids ), '%d' ) );
    $cw = $wpdb->get_results( $wpdb->prepare(
        "SELECT a.order_id, a.role, t.display_name, t.phone, t.email
         FROM {$wpdb->prefix}pjm_order_assignments a
         LEFT JOIN {$wpdb->prefix}pjm_translators t ON a.translator_id = t.id
         WHERE a.order_id IN ($ph) AND a.translator_id <> %d AND a.assignment_status NOT IN ('cancelled','declined')",
        array_merge( $oids, [ $tid ] )
    ) );
    foreach ( $cw as $r ) { $cowork[ (int) $r->order_id ][] = $r; }
}
// Pomocnik: „Co tłumaczysz" — tytuł/usługa z order_json.
$pjm_order_title = function( $json ) {
    $items = json_decode( (string) $json, true );
    if ( is_array( $items ) && ! empty( $items[0] ) ) {
        return $items[0]['title'] ?? $items[0]['name'] ?? ( $items[0]['service_label'] ?? '' );
    }
    return '';
};

$this_month = date( 'Y-m' );
$prev_month = date( 'Y-m', strtotime( 'first day of last month' ) );
$cost_q = "SELECT SUM(a.cost_total) FROM {$wpdb->prefix}pjm_order_assignments a
           LEFT JOIN {$wpdb->prefix}pjm_orders o ON a.order_id = o.id
           WHERE a.translator_id = %d AND a.is_billable = 1
           AND ( a.settlement_period = %s OR ( a.settlement_period IS NULL AND DATE_FORMAT(COALESCE(o.deadline,a.created_at),'%%Y-%%m') = %s ) )";
$cost_this = (float) $wpdb->get_var( $wpdb->prepare( $cost_q, $tid, $this_month, $this_month ) );
$cost_prev = (float) $wpdb->get_var( $wpdb->prepare( $cost_q, $tid, $prev_month, $prev_month ) );
$pending_cnt = 0;
foreach ( $assignments as $a ) { if ( in_array( $a->assignment_status, [ 'assigned', 'sent' ], true ) ) $pending_cnt++; }

$role_labels = [ 'translator'=>'Tłumacz', 'technician'=>'Obsługa techniczna' ];
$status_labels = [ 'assigned'=>'Do potwierdzenia','sent'=>'Do potwierdzenia','accepted'=>'Przyjęte','in_progress'=>'W realizacji','delivered'=>'Dostarczone','settled'=>'Rozliczone','declined'=>'Odrzucone','cancelled'=>'Anulowane' ];
$nonce = wp_create_nonce( 'pjm_translator_action' );
?>
<div class="pjm-tp">
    <div class="pjm-tp-head">
        <h2>Panel tłumacza — <?php echo esc_html( $translator['display_name'] ); ?></h2>
        <p>Twoje zlecenia i miesięczne podsumowanie kosztów. Rozliczenia przekazujemy księgowej.</p>
    </div>

    <div class="pjm-tp-kpi">
        <div class="pjm-tp-card"><span class="lbl">Do potwierdzenia</span><span class="val"><?php echo (int) $pending_cnt; ?></span></div>
        <div class="pjm-tp-card"><span class="lbl">Ten miesiąc (<?php echo esc_html( $this_month ); ?>)</span><span class="val"><?php echo number_format( $cost_this, 2, ',', ' ' ); ?> zł</span></div>
        <div class="pjm-tp-card"><span class="lbl">Poprzedni miesiąc</span><span class="val"><?php echo number_format( $cost_prev, 2, ',', ' ' ); ?> zł</span></div>
    </div>

    <h3 class="pjm-tp-h3">Twoje zlecenia</h3>
    <?php if ( empty( $assignments ) ) : ?>
        <p class="pjm-tp-empty">Brak przypisanych zleceń dla kartoteki „<strong><?php echo esc_html( $translator['display_name'] ); ?></strong>".<br>
        Jeśli realizujesz zlecenia, ale ich tu nie widzisz — administrator musiał przypisać je do tej kartoteki (możliwe, że masz inną kartotekę tłumacza).</p>
    <?php else : ?>
    <div class="pjm-tp-table-wrap">
        <table class="pjm-tp-table">
            <thead><tr><th>Zlecenie</th><th>Termin</th><th>Para</th><th>Rola</th><th>Koszt</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $assignments as $a ) :
                $term = $a->deadline ? date_i18n( 'd.m.Y H:i', strtotime( $a->deadline ) ) : '—';
            ?>
                <tr data-aid="<?php echo (int) $a->id; ?>">
                    <td><strong>#<?php echo esc_html( $a->order_number ); ?></strong>
                        <?php $co = trim( (string) $pjm_order_title( $a->order_json ) ); ?>
                        <?php if ( $co !== '' ) : ?><div class="pjm-tp-co"><?php echo esc_html( $co ); ?></div><?php endif; ?>
                        <?php if ( $a->description ) : ?><div class="pjm-tp-desc"><?php echo esc_html( mb_strimwidth( $a->description, 0, 60, '…' ) ); ?></div><?php endif; ?>
                        <?php if ( ! empty( $a->location ) ) : ?><div class="pjm-tp-where">Miejsce: <?php echo esc_html( $a->location ); ?></div><?php endif; ?>
                        <?php if ( ! empty( $a->meeting_url ) ) : ?><div class="pjm-tp-where"><a href="<?php echo esc_url( $a->meeting_url ); ?>" target="_blank" rel="noopener">Link do spotkania</a></div><?php endif; ?>
                        <?php if ( ! empty( $a->contact_name ) || ! empty( $a->contact_phone ) ) : ?><div class="pjm-tp-where">Kontakt: <?php echo esc_html( trim( $a->contact_name . ( $a->contact_phone ? ' · ' . $a->contact_phone : '' ) ) ); ?></div><?php endif; ?>
                        <?php
                        $mats = json_decode( $a->materials_json ?? '', true );
                        if ( is_array( $mats ) && $mats ) : ?>
                            <div class="pjm-tp-mats">Materiały: <?php foreach ( $mats as $mm ) : ?><a href="<?php echo esc_url( $mm['url'] ?? '' ); ?>" target="_blank" rel="noopener"><?php echo esc_html( ( $mm['name'] ?? '' ) ?: 'Materiał' ); ?></a> <?php endforeach; ?></div>
                        <?php endif; ?>
                        <?php $cws = $cowork[ (int) $a->order_id ] ?? []; if ( $cws ) : ?>
                            <div class="pjm-tp-where">Z kim: <?php
                                $parts = [];
                                foreach ( $cws as $c ) {
                                    $rc = ( $c->role === 'technician' ) ? 'obsługa techn.' : 'tłumacz';
                                    $ct = trim( ( $c->phone ?: '' ) . ( $c->email ? ' · ' . $c->email : '' ), ' ·' );
                                    $parts[] = esc_html( $c->display_name . ' (' . $rc . ')' ) . ( $ct ? ' — ' . esc_html( $ct ) : '' );
                                }
                                echo implode( '; ', $parts );
                            ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( $term ); ?></td>
                    <td><?php $tp_pair = pjm_lang_pair( $a->lang_from, $a->lang_to, $a->role ); echo $tp_pair !== '' ? esc_html( $tp_pair ) : '—'; ?></td>
                    <td><?php echo esc_html( $role_labels[ $a->role ] ?? $a->role ); ?></td>
                    <td><?php echo number_format( (float) $a->cost_total, 2, ',', ' ' ); ?> zł</td>
                    <td><span class="pjm-tp-badge st-<?php echo esc_attr( $a->assignment_status ); ?>"><?php echo esc_html( $status_labels[ $a->assignment_status ] ?? $a->assignment_status ); ?></span></td>
                    <td style="white-space:nowrap; text-align:right;">
                        <?php if ( in_array( $a->assignment_status, [ 'assigned', 'sent' ], true ) ) : ?>
                            <button class="pjm-tp-accept" data-aid="<?php echo (int) $a->id; ?>" data-st="accepted">Przyjmij</button>
                            <button class="pjm-tp-decline" data-aid="<?php echo (int) $a->id; ?>" data-st="declined">Odrzuć</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.pjm-tp { max-width: 980px; margin: 0 auto; font-family: 'Inter', system-ui, sans-serif; color: #44504A; }
.pjm-tp-head h2 { font-family: 'Plus Jakarta Sans', sans-serif; color: #16241D; margin: 0 0 4px; }
.pjm-tp-head p { color: #8A938D; font-size: 14px; margin: 0 0 24px; }
.pjm-tp-kpi { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 30px; }
.pjm-tp-card { background: #fff; border: 1px solid #E3E1D9; border-radius: 12px; padding: 18px; display: flex; flex-direction: column; gap: 4px; }
.pjm-tp-card .lbl { font-size: 12px; color: #8A938D; text-transform: uppercase; letter-spacing: .3px; }
.pjm-tp-card .val { font-size: 24px; font-weight: 800; color: #1B5E4B; }
@media(max-width:640px){ .pjm-tp-kpi{ grid-template-columns:1fr; } }
.pjm-tp-h3 { font-family: 'Plus Jakarta Sans', sans-serif; color: #16241D; font-size: 18px; margin: 0 0 12px; }
.pjm-tp-empty { color: #8A938D; }
.pjm-tp-table-wrap { background: #fff; border: 1px solid #E3E1D9; border-radius: 12px; overflow-x: auto; }
.pjm-tp-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.pjm-tp-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #8A938D; padding: 12px 14px; border-bottom: 1px solid #eee; }
.pjm-tp-table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
.pjm-tp-desc { font-size: 12px; color: #aaa; margin-top: 2px; }
.pjm-tp-mats { font-size: 12px; margin-top: 4px; } .pjm-tp-mats a { color: #1B5E4B; text-decoration: underline; margin-right: 6px; }
.pjm-tp-badge { padding: 3px 9px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.pjm-tp-badge.st-assigned{background:#fef3c7;color:#b45309;} .pjm-tp-badge.st-accepted{background:#dcfce7;color:#15803d;}
.pjm-tp-badge.st-in_progress{background:#e0f2fe;color:#0369a1;} .pjm-tp-badge.st-delivered{background:#dcfce7;color:#15803d;}
.pjm-tp-badge.st-settled{background:#ddd6fe;color:#6d28d9;} .pjm-tp-badge.st-cancelled{background:#fee2e2;color:#b91c1c;}
.pjm-tp-badge.st-sent{background:#fef3c7;color:#b45309;} .pjm-tp-badge.st-declined{background:#fee2e2;color:#b91c1c;}
.pjm-tp-where { font-size:12px; color:#42514a; margin-top:3px; } .pjm-tp-where a { color:#1B5E4B; }
.pjm-tp-co { font-size:13px; color:#16241D; font-weight:500; margin-top:2px; }
.pjm-tp-accept,.pjm-tp-decline { border:none; border-radius:6px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; }
.pjm-tp-accept { background:#1B5E4B; color:#fff; } .pjm-tp-decline { background:#f3f4f6; color:#b91c1c; margin-left:4px; }
</style>

<script>
(function(){
    var nonce = '<?php echo esc_js( $nonce ); ?>';
    var ajax = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    function act(aid, st){
        if (st === 'declined' && !confirm('Na pewno odrzucić to zlecenie?')) return;
        jQuery.post(ajax, { action:'pjm_translator_accept', assignment_id:aid, status:st, nonce:nonce }, function(res){
            if (res.success){ location.reload(); } else { alert('Błąd: ' + (res.data || '')); }
        });
    }
    jQuery(function($){
        $(document).on('click', '.pjm-tp-accept, .pjm-tp-decline', function(){
            act($(this).data('aid'), $(this).data('st'));
        });
    });
})();
</script>
