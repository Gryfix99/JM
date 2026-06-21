<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Pętle Indukcyjne — wizualny kalendarz zajętości sprzętu + zarządzanie liczbą posiadanych pętli.
 * Limit: gdy w danym dniu zajęte >= liczby pętli → dzień „pełny" (brak wolnego sprzętu).
 */
if ( ! current_user_can( 'manage_options' ) ) { echo '<p>Brak uprawnień.</p>'; return; }
global $wpdb;

$loop_units = max( 1, (int) get_option( 'pjm_loop_units', 2 ) );

$rows = $wpdb->get_results(
    "SELECT o.id, o.order_number, o.order_status, o.deadline, o.created_at, o.order_json, u.display_name, u.user_email
     FROM {$wpdb->prefix}pjm_orders o
     LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
     WHERE ( o.package_id = 'loop' OR o.order_json LIKE '%\"service_type\":\"loop\"%' )
       AND o.order_status NOT IN ( 'cancelled', 'draft' )
     ORDER BY COALESCE(o.deadline, o.created_at) DESC
     LIMIT 300"
);

$status_pl = [ 'new'=>'Nowe','pending'=>'Oczekujące','payment_pending'=>'Czeka na wpłatę','processing'=>'W realizacji','paid'=>'Opłacone','completed'=>'Zakończone' ];

$occupied = [];
$booking_rows = [];
foreach ( $rows as $r ) {
    $items = json_decode( $r->order_json, true );
    $range = ''; $tfrom = ''; $tto = '';
    if ( is_array( $items ) ) {
        foreach ( $items as $it ) {
            if ( ( $it['service_type'] ?? '' ) === 'loop' ) {
                $range = (string) ( $it['meta']['dates'] ?? '' );
                $tfrom = (string) ( $it['meta']['time_from'] ?? '' );
                $tto   = (string) ( $it['meta']['time_to'] ?? '' );
                break;
            }
        }
    }
    $time_label = ( $tfrom && $tto ) ? "$tfrom–$tto" : '';
    $d1 = $d2 = '';
    if ( preg_match_all( '/(\d{4}-\d{2}-\d{2})/', $range, $m ) && count( $m[1] ) >= 1 ) {
        $d1 = $m[1][0]; $d2 = isset( $m[1][1] ) ? $m[1][1] : $d1;
    } elseif ( $r->deadline ) {
        $d1 = $d2 = date( 'Y-m-d', strtotime( $r->deadline ) );
    }
    if ( $d1 ) {
        $t1 = strtotime( $d1 ); $t2 = strtotime( $d2 ?: $d1 );
        if ( $t1 && $t2 && $t2 >= $t1 && ( $t2 - $t1 ) <= 86400 * 120 ) {
            for ( $t = $t1; $t <= $t2; $t += 86400 ) {
                $occupied[ date( 'Y-m-d', $t ) ][] = [ 'num' => $r->order_number, 'name' => $r->display_name, 'time' => $time_label ];
            }
        }
    }
    $booking_rows[] = [
        'range' => $range ?: ( $d1 ? date_i18n( 'd.m.Y', strtotime( $d1 ) ) : '—' ),
        'num' => $r->order_number, 'name' => $r->display_name, 'email' => $r->user_email,
        'status' => $status_pl[ $r->order_status ] ?? $r->order_status,
        'ts' => $t1 ?? ( $r->deadline ? strtotime( $r->deadline ) : strtotime( $r->created_at ) ),
    ];
}

$today_ts = current_time( 'timestamp' );
$today_ymd = date( 'Y-m-d', $today_ts );
$pl_months = [ 1=>'styczeń',2=>'luty',3=>'marzec',4=>'kwiecień',5=>'maj',6=>'czerwiec',7=>'lipiec',8=>'sierpień',9=>'wrzesień',10=>'październik',11=>'listopad',12=>'grudzień' ];

$render_month = function( $year, $month ) use ( $occupied, $today_ymd, $pl_months ) {
    $first = mktime( 0, 0, 0, $month, 1, $year );
    $days_in = (int) date( 't', $first );
    $start_wd = (int) date( 'N', $first );
    echo '<div class="lc-month">';
    echo '<div class="lc-month-name">' . esc_html( ucfirst( $pl_months[ $month ] ) . ' ' . $year ) . '</div>';
    echo '<div class="lc-grid">';
    foreach ( [ 'Pn','Wt','Śr','Cz','Pt','So','Nd' ] as $wd ) echo '<div class="lc-wd">' . $wd . '</div>';
    for ( $i = 1; $i < $start_wd; $i++ ) echo '<div class="lc-empty"></div>';
    for ( $d = 1; $d <= $days_in; $d++ ) {
        $ymd = sprintf( '%04d-%02d-%02d', $year, $month, $d );
        $count = isset( $occupied[ $ymd ] ) ? count( $occupied[ $ymd ] ) : 0;
        $is_today = ( $ymd === $today_ymd );
        $title = '';
        if ( $count ) {
            $parts = array_map( function( $b ) { return '#' . $b['num'] . ' ' . $b['name'] . ( $b['time'] ? ' (' . $b['time'] . ')' : '' ); }, $occupied[ $ymd ] );
            $title = esc_attr( implode( ' • ', $parts ) );
        }
        $cls = 'lc-day' . ( $is_today ? ' lc-today' : '' );
        echo '<div class="' . $cls . '" data-count="' . $count . '"' . ( $title ? ' title="' . $title . '"' : '' ) . '>';
        echo '<span class="lc-num">' . $d . '</span>';
        echo '<span class="lc-dot"' . ( $count ? '' : ' style="display:none;"' ) . '></span>';
        echo '</div>';
    }
    echo '</div></div>';
};

$cur_y = (int) date( 'Y', $today_ts ); $cur_m = (int) date( 'n', $today_ts );
$nxt = strtotime( '+1 month', mktime( 0,0,0,$cur_m,1,$cur_y ) );
usort( $booking_rows, function( $a, $b ) { return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0); } );
?>

<div class="pjm-loopcal" data-units="<?php echo esc_attr( $loop_units ); ?>">
    <div class="pjm-loopcal-head">
        <h2><span class="material-symbols-rounded">hearing</span> Pętle Indukcyjne — zajętość sprzętu</h2>
        <p>Dni z rezerwacją są podświetlone. Gdy w dniu zajęte = liczba Twoich pętli, dzień jest <strong>pełny</strong>.</p>
    </div>

    <div class="lc-units">
        <label for="ja-loop-units">Liczba posiadanych pętli:</label>
        <input type="number" id="ja-loop-units" min="1" max="50" value="<?php echo esc_attr( $loop_units ); ?>">
        <button type="button" id="ja-loop-units-save" class="button">Zapisz</button>
        <span id="ja-loop-units-msg"></span>
    </div>

    <div class="lc-legend">
        <span><i class="lc-sw lc-sw-free"></i> Wolne</span>
        <span><i class="lc-sw lc-sw-part"></i> Częściowo zajęte</span>
        <span><i class="lc-sw lc-sw-full"></i> Pełne</span>
        <span><i class="lc-sw lc-sw-today"></i> Dziś</span>
    </div>

    <div class="lc-months">
        <?php
        $render_month( $cur_y, $cur_m );
        $render_month( (int) date( 'Y', $nxt ), (int) date( 'n', $nxt ) );
        ?>
    </div>

    <h3 class="pjm-loopcal-sub">Rezerwacje sprzętu</h3>
    <?php if ( empty( $booking_rows ) ) : ?>
        <p class="pjm-loopcal-empty">Brak zarezerwowanych terminów pętli.</p>
    <?php else : ?>
        <table class="pjm-loopcal-table">
            <thead><tr><th>Termin</th><th>Zamówienie</th><th>Klient</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ( $booking_rows as $b ) :
                    $up = ( $b['ts'] && $b['ts'] >= ( $today_ts - 86400 ) );
                ?>
                <tr class="<?php echo $up ? 'pjm-up' : 'pjm-past'; ?>">
                    <td><strong><?php echo esc_html( $b['range'] ); ?></strong><?php if ( $up ) : ?> <span class="pjm-tag-up">nadchodzące</span><?php endif; ?></td>
                    <td>#<?php echo esc_html( $b['num'] ); ?></td>
                    <td><?php echo esc_html( $b['name'] ?: 'Klient' ); ?><?php if ( $b['email'] ) : ?><div class="pjm-loopcal-mail"><?php echo esc_html( $b['email'] ); ?></div><?php endif; ?></td>
                    <td><span class="pjm-loopcal-st"><?php echo esc_html( $b['status'] ); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.pjm-loopcal { font-family:'Inter',system-ui,sans-serif; }
.pjm-loopcal-head h2 { display:flex; align-items:center; gap:8px; margin:0 0 4px; color:#16241D; }
.pjm-loopcal-head h2 .material-symbols-rounded { color:#1B5E4B; }
.pjm-loopcal-head p { color:#6b7280; font-size:13px; margin:0 0 16px; }
.lc-units { display:flex; align-items:center; gap:10px; background:#f7faf8; border:1px solid #e3e1d9; border-radius:10px; padding:12px 16px; margin-bottom:18px; flex-wrap:wrap; font-size:13px; }
.lc-units label { font-weight:600; color:#16241D; }
.lc-units input { width:72px; padding:7px 9px; border:1px solid #d1d5db; border-radius:8px; }
.lc-units .button { background:#1B5E4B; color:#fff; border:none; border-radius:8px; padding:7px 16px; cursor:pointer; }
.lc-units .button:hover { background:#154A3B; }
#ja-loop-units-msg { color:#15803d; font-weight:600; }
.lc-legend { display:flex; gap:18px; font-size:12px; color:#5f6f67; margin-bottom:16px; flex-wrap:wrap; }
.lc-legend span { display:flex; align-items:center; gap:6px; }
.lc-sw { width:14px; height:14px; border-radius:4px; display:inline-block; }
.lc-sw-free { background:#eef2f0; border:1px solid #e3e1d9; } .lc-sw-part { background:#1B5E4B; } .lc-sw-full { background:#C0392B; } .lc-sw-today { background:#fff; border:2px solid #E0A33E; }
.lc-months { display:flex; flex-wrap:wrap; gap:24px; margin-bottom:28px; }
.lc-month { flex:1; min-width:300px; }
.lc-month-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; color:#16241D; margin-bottom:10px; }
.lc-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:5px; }
.lc-wd { text-align:center; font-size:11px; font-weight:700; color:#8A938D; padding-bottom:4px; }
.lc-empty { aspect-ratio:1; }
.lc-day { aspect-ratio:1; border:1px solid #e8ece9; border-radius:8px; background:#f7faf8; position:relative; padding:5px 7px; }
.lc-day .lc-num { font-size:12px; color:#6b7280; }
.lc-day.lc-part { background:#1B5E4B; border-color:#154A3B; } .lc-day.lc-part .lc-num { color:#cfe3da; font-weight:600; }
.lc-day.lc-full { background:#C0392B; border-color:#9c2d20; } .lc-day.lc-full .lc-num { color:#ffd9d2; font-weight:700; }
.lc-day .lc-dot { position:absolute; right:5px; bottom:4px; background:#E0A33E; color:#3A2A00; font-size:10px; font-weight:800; min-width:16px; height:16px; border-radius:50px; display:flex; align-items:center; justify-content:center; padding:0 4px; }
.lc-day.lc-full .lc-dot { background:#fff; color:#C0392B; }
.lc-day.lc-today { box-shadow:0 0 0 2px #E0A33E inset; }
.pjm-loopcal-sub { font-size:15px; color:#16241D; margin:0 0 10px; }
.pjm-loopcal-table { width:100%; border-collapse:collapse; font-size:13px; }
.pjm-loopcal-table th { text-align:left; font-size:11px; text-transform:uppercase; color:#6b7280; padding:10px 8px; border-bottom:1px solid #e5e7eb; }
.pjm-loopcal-table td { padding:12px 8px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.pjm-loopcal-table tr.pjm-past { opacity:.55; }
.pjm-tag-up { display:inline-block; margin-left:6px; font-size:10px; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:50px; }
.pjm-loopcal-mail { font-size:11px; color:#9ca3af; }
.pjm-loopcal-st { background:#eef5f1; color:#1B5E4B; padding:3px 10px; border-radius:50px; font-size:12px; font-weight:600; }
.pjm-loopcal-empty { color:#9ca3af; padding:14px 0; }
@media(max-width:560px){ .lc-month{ min-width:100%; } }
</style>

<script>
jQuery(function($){
    function applyUnits(u){
        u = Math.max(1, parseInt(u,10)||1);
        $('.pjm-loopcal .lc-day').each(function(){
            var c = parseInt($(this).data('count'),10)||0;
            $(this).removeClass('lc-part lc-full');
            var $dot = $(this).find('.lc-dot');
            if (c <= 0){ $dot.hide().text(''); return; }
            $dot.show().text(c >= u ? (c+'/'+u) : c);
            $(this).addClass(c >= u ? 'lc-full' : 'lc-part');
        });
    }
    applyUnits($('.pjm-loopcal').data('units'));
    $('#ja-loop-units').on('input', function(){ applyUnits($(this).val()); });
    $('#ja-loop-units-save').on('click', function(){
        var u = $('#ja-loop-units').val();
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_save_loop_units', units:u, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success){ $('#ja-loop-units-msg').text(res.data.message); setTimeout(function(){ $('#ja-loop-units-msg').text(''); },2500); }
            else { $('#ja-loop-units-msg').css('color','#b91c1c').text('Błąd: '+(res.data||'')); }
        });
    });
});
</script>
