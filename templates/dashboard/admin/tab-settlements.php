<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$current_period = date( 'Y-m' );
?>

<div class="ja-settlements-panel">

    <div class="ja-set-header">
        <div>
            <h2 style="margin:0; font-size:18px;">
                <span class="material-symbols-rounded" style="vertical-align:middle; color:#1B5E4B;">receipt_long</span>
                Rozliczenia miesięczne
            </h2>
            <p style="margin:4px 0 0; color:var(--ja-text-light); font-size:13px;">
                Podliczenia kosztów tłumaczy (umowa zlecenie) i przychodów od firm. Dokumenty pomocnicze — handoff dla księgowej (PDF/CSV).
            </p>
        </div>
        <div class="ja-set-month">
            <label>Miesiąc:</label>
            <input type="month" id="pjm-set-period" value="<?php echo esc_attr( $current_period ); ?>">
        </div>
    </div>

    <div class="ja-set-summary" id="pjm-set-summary">
        <div class="ja-set-kpi cost"><span class="lbl">Koszty tłumaczy</span><span class="val" id="set-sum-cost">—</span></div>
        <div class="ja-set-kpi rev"><span class="lbl">Przychód (przypisany)</span><span class="val" id="set-sum-rev">—</span></div>
        <div class="ja-set-kpi mar"><span class="lbl">Marża</span><span class="val" id="set-sum-margin">—</span></div>
    </div>

    <div class="ja-set-tabs">
        <button class="ja-set-tab active" data-sub="translators">
            <span class="material-symbols-rounded">interpreter_mode</span> Tłumacze (do wypłaty)
        </button>
        <button class="ja-set-tab" data-sub="clients">
            <span class="material-symbols-rounded">business</span> Firmy (do zafakturowania)
        </button>
    </div>

    <div class="ja-card table-card" style="margin-top:0;">
        <div id="pjm-set-loading" style="padding:30px; text-align:center; color:#999;">Ładowanie…</div>
        <table class="ja-table" id="pjm-set-table" style="display:none;">
            <thead>
                <tr>
                    <th id="pjm-set-subject-col">Tłumacz</th>
                    <th>Pozycje</th>
                    <th>Kwota</th>
                    <th>Status</th>
                    <th style="text-align:right;">Dokument</th>
                </tr>
            </thead>
            <tbody id="pjm-set-tbody"></tbody>
        </table>
    </div>

    <p class="ja-set-note">
        <span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle;">info</span>
        „Generuj" tworzy wersję roboczą. „Finalizuj" zamyka miesiąc (pozycje stają się niezmienne). PDF/CSV przekaż księgowej.
        System sumuje kwoty brutto — <strong>nie liczy PIT/ZUS</strong>.
    </p>
</div>

<style>
.ja-set-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
.ja-set-month label { font-size:13px; font-weight:600; margin-right:6px; }
.ja-set-month input { padding:8px 10px; border:1px solid #dfe6e9; border-radius:8px; font-size:14px; }
.ja-set-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
.ja-set-kpi { background:#fff; border:1px solid var(--ja-border); border-radius:10px; padding:16px 18px; display:flex; flex-direction:column; gap:4px; }
.ja-set-kpi .lbl { font-size:12px; color:var(--ja-text-light); text-transform:uppercase; letter-spacing:.3px; }
.ja-set-kpi .val { font-size:22px; font-weight:800; }
.ja-set-kpi.cost .val { color:#e8590c; }
.ja-set-kpi.rev .val { color:#1d4ed8; }
.ja-set-kpi.mar .val { color:#15803d; }
@media(max-width:640px){ .ja-set-summary{ grid-template-columns:1fr; } }

.ja-set-tabs { display:flex; gap:8px; margin-bottom:0; }
.ja-set-tab { border:1px solid var(--ja-border); background:#fff; padding:10px 16px; border-radius:8px 8px 0 0; cursor:pointer; font-weight:600; font-size:13px; color:#555; display:inline-flex; align-items:center; gap:6px; }
.ja-set-tab .material-symbols-rounded { font-size:18px; }
.ja-set-tab.active { background:#fff8f0; color:#e8590c; border-bottom-color:#fff8f0; }

.set-badge { padding:3px 9px; border-radius:12px; font-size:11px; font-weight:700; }
.set-badge.set-none { background:#f3f4f6; color:#6b7280; }
.set-badge.set-draft { background:#fef3c7; color:#b45309; }
.set-badge.set-finalized { background:#dbeafe; color:#1d4ed8; }
.set-badge.set-sent { background:#dcfce7; color:#15803d; }
.set-badge.set-paid { background:#ddd6fe; color:#6d28d9; }
.set-doc { font-size:10px; color:#9ca3af; margin-top:2px; }
.ja-btn.set-mini { padding:4px 9px; font-size:12px; margin-left:3px; }
.ja-btn.set-gen { background:#1B5E4B; color:#fff; border-color:#1B5E4B; padding:5px 12px; font-size:12px; }
.ja-btn.set-final { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
.ja-btn.set-send { background:#15803d; color:#fff; border-color:#15803d; }
.ja-set-note { font-size:12px; color:#9ca3af; margin-top:14px; line-height:1.5; }
</style>

<script>
jQuery(function($){
    var state = { sub: 'translators', data: null };

    function fmt(n){ return Number(n||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,' '); }

    function loadOverview(){
        var period = $('#pjm-set-period').val();
        $('#pjm-set-loading').show().text('Ładowanie…');
        $('#pjm-set-table').hide();
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_settlement_overview', period:period, nonce:pjm_admin_vars.nonce }, function(res){
            if(!res.success){ $('#pjm-set-loading').text('Błąd: '+res.data); return; }
            state.data = res.data;
            $('#set-sum-cost').text(fmt(res.data.sum_cost)+' zł');
            $('#set-sum-rev').text(fmt(res.data.sum_revenue)+' zł');
            $('#set-sum-margin').text(fmt(res.data.sum_revenue - res.data.sum_cost)+' zł');
            render();
        }).fail(function(){ $('#pjm-set-loading').text('Błąd połączenia.'); });
    }

    function render(){
        if(!state.data) return;
        $('#pjm-set-loading').hide();
        $('#pjm-set-table').show();
        $('#pjm-set-subject-col').text(state.sub==='translators' ? 'Tłumacz' : 'Firma / klient');
        $('#pjm-set-tbody').html(state.sub==='translators' ? state.data.translators : state.data.clients);
    }

    $('#pjm-set-period').on('change', loadOverview);

    $('.ja-set-tab').on('click', function(){
        $('.ja-set-tab').removeClass('active');
        $(this).addClass('active');
        state.sub = $(this).data('sub');
        render();
    });

    // Generuj draft / przelicz
    $(document).on('click', '.set-gen', function(){
        var $b=$(this), type=$b.data('type'), subject=$b.data('subject'), period=$('#pjm-set-period').val();
        $b.prop('disabled',true).text('…');
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_generate_settlement', settlement_type:type, subject_id:subject, period:period, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success){ loadOverview(); } else { alert('Błąd: '+res.data); $b.prop('disabled',false); loadOverview(); }
        });
    });

    // Finalizuj
    $(document).on('click', '.set-final', function(){
        var id=$(this).data('id');
        if(!confirm('Zamknąć miesiąc dla tego podmiotu? Pozycje staną się niezmienne.')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_finalize_settlement', settlement_id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success){ alert('Zamknięto. Dokument: '+res.data.doc_number); loadOverview(); } else alert('Błąd: '+res.data);
        });
    });

    // Wyślij
    $(document).on('click', '.set-send', function(){
        var id=$(this).data('id');
        if(!confirm('Wysłać rozliczenie e-mailem do podmiotu?')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_send_settlement', settlement_id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success){ alert(res.data.message); loadOverview(); } else alert('Błąd: '+res.data);
        });
    });

    loadOverview();
});
</script>
