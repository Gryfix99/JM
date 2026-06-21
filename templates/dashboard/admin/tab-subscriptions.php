<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$subs = $wpdb->get_results("
    SELECT s.*, u.display_name, u.user_email
    FROM {$wpdb->prefix}pjm_subscriptions s
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
    ORDER BY s.ends_at DESC LIMIT 50
");

// Ustawienia abonamentów — plany z PJM_Pricing (z nałożonymi nadpisaniami admina).
$pjm_plans = []; $pjm_psettings = [];
if ( class_exists( 'PJM_Pricing' ) ) {
    $pr = new PJM_Pricing();
    $pjm_plans     = $pr->get_plans();
    $pjm_psettings = method_exists( $pr, 'get_plan_settings' ) ? $pr->get_plan_settings() : [];
}
?>

<div class="ja-card pjm-plans-settings" style="padding:20px; margin-bottom:20px;">
    <h3 style="margin:0 0 4px; color:#14352A;"><span class="material-symbols-rounded" style="vertical-align:-4px;">tune</span> Ustawienia abonamentów</h3>
    <p style="margin:0 0 14px; color:#7a857f; font-size:13px;">Edytuj nazwę, cenę i miesięczny limit każdego planu. Stripe ID i funkcje pozostają z konfiguracji.</p>

    <div class="pjm-plan-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
        <?php foreach ( $pjm_plans as $pid => $p ) : ?>
        <div class="pjm-plan-box" data-plan="<?php echo esc_attr( $pid ); ?>" style="border:1px solid #e5e7eb; border-radius:10px; padding:12px;">
            <div style="font-size:11px; text-transform:uppercase; color:#9ca3af; margin-bottom:8px;"><?php echo esc_html( $pid ); ?></div>
            <label style="font-size:12px; color:#6b7280;">Nazwa</label>
            <input type="text" class="pl-name" value="<?php echo esc_attr( $p['name'] ?? '' ); ?>" style="width:100%; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; margin-bottom:8px; box-sizing:border-box;">
            <label style="font-size:12px; color:#6b7280;">Podtytuł</label>
            <input type="text" class="pl-subtitle" value="<?php echo esc_attr( $p['subtitle'] ?? '' ); ?>" style="width:100%; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; margin-bottom:8px; box-sizing:border-box;">
            <div style="display:flex; gap:8px;">
                <div style="flex:1;"><label style="font-size:12px; color:#6b7280;">Cena (zł)</label>
                    <input type="number" class="pl-price" step="1" min="0" value="<?php echo esc_attr( $p['price'] ?? 0 ); ?>" style="width:100%; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; box-sizing:border-box;"></div>
                <div style="flex:1;"><label style="font-size:12px; color:#6b7280;">Limit / mies.</label>
                    <input type="text" class="pl-limit" value="<?php echo esc_attr( $p['limit_val'] ?? '' ); ?>" style="width:100%; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; box-sizing:border-box;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:flex; gap:18px; flex-wrap:wrap; margin-top:14px; align-items:end;">
        <div><label style="font-size:12px; color:#6b7280;">Rollover (%)</label><br>
            <input type="number" id="pl-rollover" min="0" max="100" value="<?php echo esc_attr( $pjm_psettings['rollover_percentage'] ?? 30 ); ?>" style="width:90px; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px;"></div>
        <div><label style="font-size:12px; color:#6b7280;">Płatnych miesięcy w roku</label><br>
            <input type="number" id="pl-yearly" min="1" max="12" value="<?php echo esc_attr( $pjm_psettings['yearly_months_pay'] ?? 11 ); ?>" style="width:90px; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px;"></div>
        <button type="button" id="pjm-save-plans" class="ja-btn ja-btn-primary" style="background:#14352A; color:#fff; border:none; border-radius:8px; padding:10px 20px; font-weight:600; cursor:pointer;">Zapisz ustawienia</button>
        <span id="pjm-plans-msg" style="font-size:13px; color:#0F6E56;"></span>
    </div>
</div>

<script>
jQuery(function($){
    $('#pjm-save-plans').on('click', function(){
        var $b=$(this); $b.prop('disabled',true);
        var data={ action:'pjm_admin_save_plans', nonce:pjm_admin_vars.nonce,
                   settings:{ rollover_percentage:$('#pl-rollover').val(), yearly_months_pay:$('#pl-yearly').val() }, plans:{} };
        $('.pjm-plan-box').each(function(){
            var id=$(this).data('plan');
            data.plans[id]={ name:$(this).find('.pl-name').val(), subtitle:$(this).find('.pl-subtitle').val(),
                             price:$(this).find('.pl-price').val(), limit_val:$(this).find('.pl-limit').val() };
        });
        $.post(pjm_admin_vars.ajax_url, data, function(res){
            $b.prop('disabled',false);
            $('#pjm-plans-msg').text(res.success ? (res.data.message||'Zapisano.') : ('Błąd: '+(res.data||'')));
        }).fail(function(){ $b.prop('disabled',false); $('#pjm-plans-msg').text('Błąd połączenia.'); });
    });
});
</script>
<?php

<div class="ja-card">
    <table class="ja-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Klient</th>
                <th>Plan</th>
                <th>Wygasa</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($subs as $s): 
                $days_left = (strtotime($s->ends_at) - time()) / 86400;
                $status_class = ($s->status === 'active') ? 'success' : 'gray';
            ?>
            <tr>
                <td>#<?php echo $s->id; ?></td>
                <td>
                    <strong><?php echo esc_html($s->display_name); ?></strong><br>
                    <small><?php echo esc_html($s->user_email); ?></small>
                </td>
                <td style="text-transform:uppercase; font-weight:bold;"><?php echo esc_html($s->plan_id); ?></td>
                <td>
                    <?php echo date('Y-m-d', strtotime($s->ends_at)); ?>
                    <small style="color:<?php echo $days_left < 7 ? 'red' : 'gray'; ?>">
                        (<?php echo round($days_left); ?> dni)
                    </small>
                </td>
                <td><span class="ja-badge <?php echo $status_class; ?>"><?php echo $s->status; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>