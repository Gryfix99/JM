<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;
$cur = date( 'Y-m' );
?>
<div class="ja-reports-panel">
    <div class="ja-rep-header">
        <div>
            <h2 style="margin:0; font-size:18px;">
                <span class="material-symbols-rounded" style="vertical-align:middle; color:var(--pjm-primary,#1B5E4B);">monitoring</span>
                Raport miesięczny
            </h2>
            <p style="margin:4px 0 0; color:var(--ja-text-light); font-size:13px;">Przychód, koszt tłumaczy i marża w wybranym miesiącu. Dane poglądowe — rozliczenia w zakładce „Rozliczenia".</p>
        </div>
        <div><label style="font-size:13px; font-weight:600; margin-right:6px;">Miesiąc:</label>
            <input type="month" id="pjm-rep-period" value="<?php echo esc_attr( $cur ); ?>" style="padding:8px 10px; border:1px solid var(--ja-border,#dfe6e9); border-radius:8px;"></div>
    </div>

    <div class="ja-rep-kpi">
        <div class="ja-rep-card rev"><span class="lbl">Przychód</span><span class="val" id="rep-revenue">—</span></div>
        <div class="ja-rep-card cost"><span class="lbl">Koszt tłumaczy</span><span class="val" id="rep-cost">—</span></div>
        <div class="ja-rep-card mar"><span class="lbl">Marża</span><span class="val" id="rep-margin">—</span></div>
        <div class="ja-rep-card cnt"><span class="lbl">Zamówienia</span><span class="val" id="rep-orders">—</span></div>
    </div>

    <div class="ja-card table-card" style="margin-top:18px;">
        <h3 style="margin:0; padding:16px 18px; border-bottom:1px solid var(--ja-border); font-size:14px;">Sprzedaż wg usługi</h3>
        <table class="ja-table">
            <thead><tr><th>Usługa</th><th>Liczba</th><th style="text-align:right;">Wartość</th></tr></thead>
            <tbody id="pjm-rep-services"><tr><td colspan="3" style="text-align:center; color:#999; padding:24px;">Ładowanie…</td></tr></tbody>
        </table>
    </div>
</div>

<style>
.ja-rep-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
.ja-rep-kpi { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.ja-rep-card { background:#fff; border:1px solid var(--ja-border); border-radius:10px; padding:16px 18px; display:flex; flex-direction:column; gap:4px; }
.ja-rep-card .lbl { font-size:12px; color:var(--ja-text-light); text-transform:uppercase; letter-spacing:.3px; }
.ja-rep-card .val { font-size:22px; font-weight:800; }
.ja-rep-card.rev .val { color:#1d4ed8; } .ja-rep-card.cost .val { color:#e8590c; }
.ja-rep-card.mar .val { color:var(--pjm-primary,#15803d); } .ja-rep-card.cnt .val { color:#16241D; }
@media(max-width:700px){ .ja-rep-kpi{ grid-template-columns:1fr 1fr; } }
</style>

<script>
jQuery(function($){
    function fmt(n){ return Number(n||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,' '); }
    function load(){
        var p=$('#pjm-rep-period').val();
        $('#pjm-rep-services').html('<tr><td colspan="3" style="text-align:center;color:#999;padding:24px;">Ładowanie…</td></tr>');
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_report', period:p, nonce:pjm_admin_vars.nonce }, function(res){
            if(!res.success){ alert('Błąd: '+(res.data||'')); return; }
            var d=res.data;
            $('#rep-revenue').text(fmt(d.revenue)+' zł');
            $('#rep-cost').text(fmt(d.cost)+' zł');
            $('#rep-margin').text(fmt(d.margin)+' zł');
            $('#rep-orders').text(d.paid_count+' / '+d.orders_count);
            $('#pjm-rep-services').html(d.by_service);
        }).fail(function(){ alert('Błąd połączenia.'); });
    }
    $('#pjm-rep-period').on('change', load);
    load();
});
</script>
