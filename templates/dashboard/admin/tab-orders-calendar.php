<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;

$A = "{$wpdb->prefix}pjm_order_assignments";
$O = "{$wpdb->prefix}pjm_orders";
$T = "{$wpdb->prefix}pjm_translators";

// Okno: od miesiąca wstecz do roku w przód (renderowanie po stronie klienta).
$tz    = new DateTimeZone( 'Europe/Warsaw' );
$today = new DateTime( 'now', $tz );
$from  = ( clone $today )->modify( 'first day of -1 month' )->format( 'Y-m-d' );
$to    = ( clone $today )->modify( 'first day of +12 month' )->format( 'Y-m-d' );

$rows = $wpdb->get_results( $wpdb->prepare("
    SELECT a.order_id, a.work_date, a.assignment_status, t.display_name AS tr_name
    FROM {$A} a
    LEFT JOIN {$T} t ON a.translator_id = t.id
    WHERE a.work_date IS NOT NULL
      AND a.work_date BETWEEN %s AND %s
      AND a.assignment_status NOT IN ('cancelled','declined')
    ORDER BY a.work_date ASC, a.id ASC
    LIMIT 500
", $from, $to ) );

$events = [];
foreach ( $rows as $r ) {
    $events[] = [
        'd'   => substr( $r->work_date, 0, 10 ),
        'oid' => (int) $r->order_id,
        't'   => $r->tr_name ?: 'Tłumacz',
        'st'  => $r->assignment_status,
    ];
}
?>

<style>
    .pjm-cal { background:#fff; border:1px solid #e3e8e5; border-radius:12px; box-shadow:0 2px 6px rgba(20,53,42,.05); margin-top:20px; overflow:hidden; }
    .pjm-cal-bar { display:flex; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid #eef2f0; }
    .pjm-cal-title { margin:0; font-size:16px; color:#14352A; font-weight:500; min-width:170px; }
    .pjm-cal-nav { display:flex; gap:6px; }
    .pjm-cal-nav button { width:34px; height:34px; border:1px solid #e3e8e5; background:#fbfcfc; border-radius:8px; cursor:pointer; color:#14352A; display:inline-flex; align-items:center; justify-content:center; }
    .pjm-cal-nav button:hover { border-color:#cfe0d7; }
    .pjm-cal-today { margin-left:auto; border:1px solid #e3e8e5; background:#fbfcfc; border-radius:8px; padding:7px 14px; cursor:pointer; color:#14352A; font:inherit; }
    .pjm-cal-today:hover { border-color:#cfe0d7; }

    .pjm-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:#eef2f0; }
    .pjm-cal-dow { background:#f7faf8; padding:8px 6px; text-align:center; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#586660; }
    .pjm-cal-cell { background:#fff; min-height:96px; padding:6px 7px; }
    .pjm-cal-cell.muted { background:#fafbfb; }
    .pjm-cal-cell.today { background:#f3f9f6; box-shadow:inset 0 0 0 2px #1B5E4B; }
    .pjm-cal-dnum { font-size:12px; color:#5b655f; font-weight:600; }
    .pjm-cal-cell.today .pjm-cal-dnum { color:#15452f; }
    .pjm-cal-ev { display:block; width:100%; text-align:left; border:none; border-radius:6px; padding:3px 7px; margin-top:4px; font-size:11.5px; cursor:pointer; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#16241D; }
    .pjm-cal-ev .d { font-weight:700; opacity:.75; }
    .pjm-cal-empty { padding:40px 20px; text-align:center; color:#5b655f; font-size:13px; }

    /* Dostępność: widoczny focus dla klawiatury + legenda statusów (kolor nie jest jedynym nośnikiem). */
    .pjm-cal-nav button:focus-visible, .pjm-cal-today:focus-visible, .pjm-cal-ev:focus-visible { outline:3px solid #E0A33E; outline-offset:2px; }
    .pjm-cal-legend { display:flex; flex-wrap:wrap; gap:14px; padding:12px 20px; border-top:1px solid #eef2f0; font-size:11.5px; color:#586660; }
    .pjm-cal-legend span { display:inline-flex; align-items:center; gap:6px; }
    .pjm-cal-sw { width:11px; height:11px; border-radius:3px; display:inline-block; border:1px solid rgba(0,0,0,.10); }
    @media (max-width:680px){
        .pjm-cal-cell { min-height:66px; padding:4px; }
        .pjm-cal-ev { font-size:10.5px; padding:2px 5px; }
        .pjm-cal-dow { font-size:10px; }
    }
</style>

<div class="pjm-cal" id="pjm-cal">
    <div class="pjm-cal-bar">
        <h3 class="pjm-cal-title" id="pjm-cal-title">—</h3>
        <div class="pjm-cal-nav">
            <button type="button" id="pjm-cal-prev" aria-label="Poprzedni miesiąc"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></button>
            <button type="button" id="pjm-cal-next" aria-label="Następny miesiąc"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
        </div>
        <button type="button" class="pjm-cal-today" id="pjm-cal-now">Dziś</button>
    </div>
    <div id="pjm-cal-body" aria-live="polite"></div>
    <div class="pjm-cal-legend">
        <span><i class="pjm-cal-sw" style="background:#eef2f0" aria-hidden="true"></i> przydzielone</span>
        <span><i class="pjm-cal-sw" style="background:#faeeda" aria-hidden="true"></i> wysłane — oczekuje</span>
        <span><i class="pjm-cal-sw" style="background:#e1f5ee" aria-hidden="true"></i> przyjęte / realizacja</span>
        <span><i class="pjm-cal-sw" style="background:#e6f1fb" aria-hidden="true"></i> dostarczone / rozliczone</span>
    </div>
</div>

<script>
(function(){
    var EVENTS = <?php echo wp_json_encode( $events ); ?> || [];
    var TODAY  = "<?php echo esc_js( $today->format( 'Y-m-d' ) ); ?>";
    var MONTHS = ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec','Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];
    var DOW    = ['Pon','Wt','Śr','Czw','Pt','Sob','Ndz'];
    var STCOL  = {
        assigned:    '#eef2f0', sent: '#faeeda', accepted: '#e1f5ee',
        in_progress: '#e1f5ee', delivered: '#e6f1fb', settled: '#e6f1fb'
    };
    var STLAB  = {
        assigned:'przydzielone', sent:'wysłane — oczekuje', accepted:'przyjęte',
        in_progress:'w realizacji', delivered:'dostarczone', settled:'rozliczone'
    };

    var byDay = {};
    EVENTS.forEach(function(e){ (byDay[e.d] = byDay[e.d] || []).push(e); });

    var cur = new Date(TODAY + 'T00:00:00');
    cur.setDate(1);

    function pad(n){ return (n<10?'0':'') + n; }
    function ymd(y,m,d){ return y + '-' + pad(m+1) + '-' + pad(d); }
    function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function escAttr(s){ return esc(s).replace(/"/g,'&quot;'); }

    function render(){
        var y = cur.getFullYear(), m = cur.getMonth();
        document.getElementById('pjm-cal-title').textContent = MONTHS[m] + ' ' + y;

        var first = new Date(y, m, 1);
        var startDow = (first.getDay() + 6) % 7; // poniedziałek = 0
        var daysIn = new Date(y, m+1, 0).getDate();
        var prevDays = new Date(y, m, 0).getDate();

        var html = '<div class="pjm-cal-grid">';
        DOW.forEach(function(d){ html += '<div class="pjm-cal-dow">' + d + '</div>'; });

        var cells = 42, monthHas = false;
        for (var i = 0; i < cells; i++){
            var dayNum, cy = y, cm = m, muted = false;
            if (i < startDow){ dayNum = prevDays - startDow + 1 + i; cm = m-1; muted = true; }
            else if (i >= startDow + daysIn){ dayNum = i - (startDow + daysIn) + 1; cm = m+1; muted = true; }
            else { dayNum = i - startDow + 1; }
            var dt = new Date(cy, cm, dayNum);
            var key = ymd(dt.getFullYear(), dt.getMonth(), dt.getDate());
            var cls = 'pjm-cal-cell' + (muted ? ' muted' : '') + (key === TODAY ? ' today' : '');
            html += '<div class="' + cls + '"><div class="pjm-cal-dnum">' + dayNum + '</div>';
            (byDay[key] || []).forEach(function(e){
                if (!muted) monthHas = true;
                var bg = STCOL[e.st] || '#eef2f0';
                var stl = STLAB[e.st] || e.st;
                // Etykieta niesie status SŁOWNIE — kolor nie jest jedynym nośnikiem informacji (WCAG 1.4.1).
                var lbl = 'Zamówienie #' + e.oid + ' — ' + e.t + ' — ' + stl + '. Otwórz szczegóły.';
                html += '<button type="button" class="pjm-cal-ev btn-details" data-id="' + e.oid +
                        '" style="background:' + bg + ';" title="' + escAttr(lbl) + '" aria-label="' + escAttr(lbl) + '">' +
                        '<span class="d">#' + e.oid + '</span> ' + esc(e.t) + '</button>';
            });
            html += '</div>';
        }
        html += '</div>';
        document.getElementById('pjm-cal-body').innerHTML = html;
    }

    document.getElementById('pjm-cal-prev').addEventListener('click', function(){ cur.setMonth(cur.getMonth()-1); render(); });
    document.getElementById('pjm-cal-next').addEventListener('click', function(){ cur.setMonth(cur.getMonth()+1); render(); });
    document.getElementById('pjm-cal-now').addEventListener('click', function(){ cur = new Date(TODAY + 'T00:00:00'); cur.setDate(1); render(); });
    render();
})();
</script>
