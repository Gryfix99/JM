<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$coupons_tbl = $wpdb->prefix . 'pjm_system_coupons';
$coupons = $wpdb->get_results( "SELECT * FROM {$coupons_tbl} ORDER BY created_at DESC" ) ?: [];

if ( ! function_exists( 'pjm_coupon_value_str' ) ) {
    function pjm_coupon_value_str( $c ) {
        return $c->type === 'fixed'
            ? number_format( (float) $c->amount, 2, ',', ' ' ) . ' zł'
            : rtrim( rtrim( number_format( (float) $c->amount, 2, '.', '' ), '0' ), '.' ) . '%';
    }
}
?>
<div class="pjm-coupons">
    <div class="pjm-cpn-head">
        <div>
            <h2 style="margin:0;">Kupony rabatowe</h2>
            <p style="margin:4px 0 0; color:#7a857f;">Kody zniżkowe z limitem globalnym i <strong>na użytkownika</strong> (np. „1 raz na osobę").</p>
        </div>
        <button type="button" class="ja-btn ja-btn-primary" id="pjm-cpn-add"><span class="material-symbols-rounded">add</span> Dodaj kupon</button>
    </div>

    <div class="pjm-cpn-tablewrap">
        <table class="pjm-cpn-table">
            <thead>
                <tr><th>Kod</th><th>Typ / wartość</th><th>Użycia (globalnie)</th><th>Limit/os.</th><th>Ważność</th><th></th></tr>
            </thead>
            <tbody id="pjm-cpn-rows">
                <?php if ( empty( $coupons ) ) : ?>
                    <tr class="pjm-cpn-empty"><td colspan="6">Brak kuponów. Kliknij „Dodaj kupon".</td></tr>
                <?php else : foreach ( $coupons as $c ) :
                    $exp = ( ! empty( $c->expiry_date ) && $c->expiry_date !== '0000-00-00 00:00:00' ) ? date_i18n( 'd.m.Y', strtotime( $c->expiry_date ) ) : '—';
                    $expired = ( ! empty( $c->expiry_date ) && strtotime( $c->expiry_date ) < time() );
                    $limit_str = ( (int) $c->usage_limit > 0 ) ? ( (int) $c->used_count . ' / ' . (int) $c->usage_limit ) : ( (int) $c->used_count . ' / ∞' );
                    $per_user  = ( (int) $c->usage_limit_per_user > 0 ) ? (int) $c->usage_limit_per_user . '×' : 'bez limitu';
                ?>
                    <tr data-id="<?php echo (int) $c->id; ?>"
                        data-code="<?php echo esc_attr( $c->code ); ?>"
                        data-type="<?php echo esc_attr( $c->type ); ?>"
                        data-amount="<?php echo esc_attr( $c->amount ); ?>"
                        data-usage="<?php echo (int) $c->usage_limit; ?>"
                        data-peruser="<?php echo (int) $c->usage_limit_per_user; ?>"
                        data-expiry="<?php echo esc_attr( ! empty( $c->expiry_date ) ? substr( $c->expiry_date, 0, 10 ) : '' ); ?>">
                        <td><strong class="pjm-cpn-code"><?php echo esc_html( $c->code ); ?></strong></td>
                        <td><?php echo esc_html( pjm_coupon_value_str( $c ) ); ?> <span class="pjm-cpn-small"><?php echo $c->type === 'fixed' ? 'kwotowy' : 'procentowy'; ?></span></td>
                        <td><?php echo esc_html( $limit_str ); ?></td>
                        <td><?php echo esc_html( $per_user ); ?></td>
                        <td><?php echo esc_html( $exp ); ?><?php echo $expired ? ' <span class="pjm-cpn-exp">wygasł</span>' : ''; ?></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="pjm-cpn-ic pjm-cpn-edit" title="Edytuj"><span class="material-symbols-rounded">edit</span></button>
                            <button class="pjm-cpn-ic pjm-cpn-del" title="Usuń"><span class="material-symbols-rounded">delete</span></button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Formularz (dodawanie / edycja) -->
    <div class="pjm-cpn-formwrap" id="pjm-cpn-form" style="display:none;">
        <h3 id="pjm-cpn-form-title">Nowy kupon</h3>
        <input type="hidden" id="cpn-id" value="0">
        <div class="pjm-cpn-grid">
            <div class="pjm-cpn-fld" style="grid-column:1/-1;">
                <label>Kod kuponu</label>
                <input type="text" id="cpn-code" placeholder="np. LATO10" style="text-transform:uppercase;">
            </div>
            <div class="pjm-cpn-fld">
                <label>Typ</label>
                <select id="cpn-type">
                    <option value="percentage">Procentowy (%)</option>
                    <option value="fixed">Kwotowy (zł)</option>
                </select>
            </div>
            <div class="pjm-cpn-fld">
                <label>Wartość <span class="pjm-cpn-small" id="cpn-val-unit">(%)</span></label>
                <input type="number" id="cpn-amount" step="0.01" min="0" value="0">
            </div>
            <div class="pjm-cpn-fld">
                <label>Limit globalny <span class="pjm-cpn-small">(0 = bez limitu)</span></label>
                <input type="number" id="cpn-usage" step="1" min="0" value="0">
            </div>
            <div class="pjm-cpn-fld">
                <label>Limit na osobę <span class="pjm-cpn-small">(0 = bez limitu)</span></label>
                <input type="number" id="cpn-peruser" step="1" min="0" value="0">
            </div>
            <div class="pjm-cpn-fld">
                <label>Ważny do <span class="pjm-cpn-small">(opcjonalnie)</span></label>
                <input type="date" id="cpn-expiry">
            </div>
        </div>
        <div class="pjm-cpn-foot">
            <button type="button" class="ja-btn ja-btn-primary" id="pjm-cpn-save">Zapisz kupon</button>
            <button type="button" class="ja-btn" id="pjm-cpn-cancel">Anuluj</button>
        </div>
    </div>
</div>

<style>
.pjm-coupons { font-family:'Inter',system-ui,sans-serif; }
.pjm-cpn-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
.pjm-cpn-tablewrap { background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
.pjm-cpn-table { width:100%; border-collapse:collapse; font-size:14px; }
.pjm-cpn-table th { text-align:left; font-size:11px; text-transform:uppercase; color:#8a938d; padding:12px 14px; border-bottom:1px solid #eee; }
.pjm-cpn-table td { padding:12px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.pjm-cpn-table tr:last-child td { border-bottom:none; }
.pjm-cpn-code { background:#eef5f1; color:#1B5E4B; padding:3px 9px; border-radius:6px; font-weight:700; letter-spacing:.5px; }
.pjm-cpn-small { font-size:11px; color:#9ca3af; font-weight:400; }
.pjm-cpn-exp { background:#fee2e2; color:#b91c1c; font-size:10px; padding:2px 6px; border-radius:6px; }
.pjm-cpn-empty td { color:#9ca3af; text-align:center; padding:24px; }
.pjm-cpn-ic { border:none; background:#f1f3f5; width:32px; height:32px; border-radius:7px; cursor:pointer; color:#555; }
.pjm-cpn-ic:hover { background:#e9ecef; color:#1B5E4B; } .pjm-cpn-ic .material-symbols-rounded { font-size:18px; vertical-align:middle; }
.pjm-cpn-del:hover { color:#b91c1c; }
.pjm-cpn-formwrap { margin-top:18px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
.pjm-cpn-formwrap h3 { margin:0 0 14px; color:#16241D; }
.pjm-cpn-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
@media(max-width:680px){ .pjm-cpn-grid { grid-template-columns:1fr 1fr; } }
.pjm-cpn-fld label { display:block; font-size:12px; font-weight:600; color:#42514a; margin-bottom:4px; }
.pjm-cpn-fld input, .pjm-cpn-fld select { width:100%; padding:9px 11px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box; }
.pjm-cpn-foot { display:flex; gap:10px; margin-top:16px; }
</style>

<script>
jQuery(function($){
    var $form = $('#pjm-cpn-form');

    function syncUnit(){ $('#cpn-val-unit').text($('#cpn-type').val() === 'fixed' ? '(zł)' : '(%)'); }
    $(document).on('change', '#cpn-type', syncUnit);

    function openForm(data){
        $('#cpn-id').val(data.id || 0);
        $('#cpn-code').val(data.code || '');
        $('#cpn-type').val(data.type || 'percentage');
        $('#cpn-amount').val(data.amount || 0);
        $('#cpn-usage').val(data.usage || 0);
        $('#cpn-peruser').val(data.peruser || 0);
        $('#cpn-expiry').val(data.expiry || '');
        $('#pjm-cpn-form-title').text(data.id ? ('Edytuj kupon: ' + (data.code||'')) : 'Nowy kupon');
        syncUnit();
        $form.slideDown(150);
        $('html,body').animate({ scrollTop: $form.offset().top - 80 }, 200);
    }

    $(document).on('click', '#pjm-cpn-add', function(){ openForm({}); });
    $(document).on('click', '#pjm-cpn-cancel', function(){ $form.slideUp(150); });

    $(document).on('click', '.pjm-cpn-edit', function(){
        var r = $(this).closest('tr');
        openForm({
            id: r.data('id'), code: r.data('code'), type: r.data('type'),
            amount: r.data('amount'), usage: r.data('usage'), peruser: r.data('peruser'), expiry: r.data('expiry')
        });
    });

    $(document).on('click', '#pjm-cpn-save', function(){
        var $b = $(this); $b.prop('disabled', true).text('Zapisywanie…');
        $.post(pjm_admin_vars.ajax_url, {
            action: 'pjm_admin_save_coupon',
            nonce: pjm_admin_vars.nonce,
            id: $('#cpn-id').val(),
            code: $('#cpn-code').val(),
            type: $('#cpn-type').val(),
            amount: $('#cpn-amount').val(),
            usage_limit: $('#cpn-usage').val(),
            usage_limit_per_user: $('#cpn-peruser').val(),
            expiry_date: $('#cpn-expiry').val()
        }, function(res){
            $b.prop('disabled', false).text('Zapisz kupon');
            if (res.success) { reloadRows(); $form.slideUp(150); }
            else alert('Błąd: ' + (res.data || ''));
        });
    });

    $(document).on('click', '.pjm-cpn-del', function(){
        var r = $(this).closest('tr'), id = r.data('id');
        if (!confirm('Usunąć kupon ' + r.data('code') + '?')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_delete_coupon', nonce:pjm_admin_vars.nonce, id:id }, function(res){
            if (res.success) r.fadeOut(150, function(){ $(this).remove(); }); else alert('Błąd: ' + (res.data || ''));
        });
    });

    function esc(s){ return $('<div>').text(s == null ? '' : s).html(); }
    function valStr(c){
        if (c.type === 'fixed') return (parseFloat(c.amount).toFixed(2).replace('.', ',')) + ' zł';
        return (parseFloat(c.amount).toString()) + '%';
    }
    function reloadRows(){
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_get_coupons', nonce:pjm_admin_vars.nonce }, function(res){
            if (!res.success) return;
            var rows = res.data.coupons || [], html = '';
            if (!rows.length) { html = '<tr class="pjm-cpn-empty"><td colspan="6">Brak kuponów. Kliknij „Dodaj kupon".</td></tr>'; }
            rows.forEach(function(c){
                var exp = (c.expiry_date && c.expiry_date.indexOf('0000') !== 0) ? c.expiry_date.substring(0,10) : '';
                var expShow = exp ? exp.split('-').reverse().join('.') : '—';
                var expired = exp && (new Date(c.expiry_date).getTime() < Date.now());
                var lim = (parseInt(c.usage_limit,10) > 0) ? (c.used_count + ' / ' + c.usage_limit) : (c.used_count + ' / ∞');
                var pu = (parseInt(c.usage_limit_per_user,10) > 0) ? (c.usage_limit_per_user + '×') : 'bez limitu';
                html += '<tr data-id="'+c.id+'" data-code="'+esc(c.code)+'" data-type="'+esc(c.type)+'" data-amount="'+esc(c.amount)+'" data-usage="'+parseInt(c.usage_limit,10)+'" data-peruser="'+parseInt(c.usage_limit_per_user,10)+'" data-expiry="'+exp+'">'
                    + '<td><strong class="pjm-cpn-code">'+esc(c.code)+'</strong></td>'
                    + '<td>'+esc(valStr(c))+' <span class="pjm-cpn-small">'+(c.type==='fixed'?'kwotowy':'procentowy')+'</span></td>'
                    + '<td>'+esc(lim)+'</td><td>'+esc(pu)+'</td>'
                    + '<td>'+esc(expShow)+(expired?' <span class="pjm-cpn-exp">wygasł</span>':'')+'</td>'
                    + '<td style="text-align:right; white-space:nowrap;"><button class="pjm-cpn-ic pjm-cpn-edit" title="Edytuj"><span class="material-symbols-rounded">edit</span></button> <button class="pjm-cpn-ic pjm-cpn-del" title="Usuń"><span class="material-symbols-rounded">delete</span></button></td></tr>';
            });
            $('#pjm-cpn-rows').html(html);
        });
    }
});
</script>
