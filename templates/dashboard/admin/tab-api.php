<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$api_keys = $wpdb->get_results(
    "SELECT id, label, key_prefix, scopes, last_used, revoked_at, created_at
     FROM {$wpdb->prefix}pjm_api_keys ORDER BY created_at DESC"
);

$scope_labels = function_exists( 'pjm_api_key_scopes' ) ? pjm_api_key_scopes() : [
    'orders:read'      => 'Odczyt zamówień',
    'products:deliver' => 'Dostarczanie produktów',
    'webhooks:receive' => 'Odbiór webhooków',
];
?>

<div class="ja-api-panel">

    <div class="ja-tr-header">
        <div>
            <h2 style="margin:0; font-size:18px;">
                <span class="material-symbols-rounded" style="vertical-align:middle; color:#1B5E4B;">key</span>
                Klucze API
            </h2>
            <p style="margin:4px 0 0; color:var(--ja-text-light); font-size:13px;">
                Osobny klucz dla każdego partnera, z własnym zakresem uprawnień. Zastępuje jeden współdzielony sekret.
                Pełny klucz pokazywany jest <strong>tylko raz</strong> przy utworzeniu — w bazie trzymany jest wyłącznie jego skrót.
            </p>
        </div>
        <button class="ja-btn ja-btn-primary" id="pjm-api-add">
            <span class="material-symbols-rounded">add</span> Nowy klucz
        </button>
    </div>

    <!-- BOX: pełny klucz po utworzeniu (jednorazowy) -->
    <div class="ja-api-newkey" id="pjm-api-newkey" style="display:none;">
        <div class="ja-api-newkey-head">
            <span class="material-symbols-rounded">warning</span>
            Skopiuj klucz teraz — nie zobaczysz go ponownie.
        </div>
        <div class="ja-api-newkey-row">
            <code id="pjm-api-newkey-value"></code>
            <button class="ja-btn ja-btn-primary" id="pjm-api-copy">
                <span class="material-symbols-rounded">content_copy</span> Kopiuj
            </button>
        </div>
        <p class="ja-small" style="margin:8px 0 0; color:#7a5c00;">
            Przekaż go partnerowi bezpiecznym kanałem. Po zamknięciu tego komunikatu klucz zniknie.
        </p>
    </div>

    <div class="ja-card table-card" style="margin-top:18px;">
        <table class="ja-table">
            <thead>
                <tr>
                    <th>Etykieta</th>
                    <th>Prefiks</th>
                    <th>Zakresy</th>
                    <th>Ostatnie użycie</th>
                    <th>Status</th>
                    <th style="text-align:right;">Akcje</th>
                </tr>
            </thead>
            <tbody id="pjm-api-tbody">
                <?php if ( empty( $api_keys ) ) : ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#777;">Brak kluczy.</td></tr>
                <?php endif; ?>
                <?php foreach ( $api_keys as $k ) :
                    $is_revoked = ! empty( $k->revoked_at );
                    $scopes = array_filter( array_map( 'trim', explode( ',', (string) $k->scopes ) ) );
                ?>
                <tr<?php echo $is_revoked ? ' style="opacity:0.55;"' : ''; ?>>
                    <td><strong><?php echo esc_html( $k->label ); ?></strong></td>
                    <td><code class="ja-api-prefix"><?php echo esc_html( $k->key_prefix ); ?>…</code></td>
                    <td>
                        <?php if ( empty( $scopes ) ) : ?>
                            <span style="color:#bbb;">—</span>
                        <?php else : foreach ( $scopes as $sc ) : ?>
                            <span class="ja-scope-chip"><?php echo esc_html( $sc ); ?></span>
                        <?php endforeach; endif; ?>
                    </td>
                    <td>
                        <?php echo $k->last_used
                            ? esc_html( date_i18n( 'd.m.Y H:i', strtotime( $k->last_used ) ) )
                            : '<span style="color:#bbb;">nigdy</span>'; ?>
                    </td>
                    <td>
                        <?php if ( $is_revoked ) : ?>
                            <span class="ja-api-badge ja-api-revoked">Odwołany</span>
                        <?php else : ?>
                            <span class="ja-api-badge ja-api-active">Aktywny</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <?php if ( ! $is_revoked ) : ?>
                        <button class="ja-icon-btn pjm-api-revoke" data-id="<?php echo (int) $k->id; ?>" data-label="<?php echo esc_attr( $k->label ); ?>" title="Odwołaj">
                            <span class="material-symbols-rounded">block</span>
                        </button>
                        <?php else : ?>
                            <span style="color:#bbb;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: nowy klucz -->
<div class="pjm-tr-modal" id="pjm-api-modal">
    <div class="pjm-tr-modal-box">
        <div class="pjm-tr-modal-head">
            <h3>Nowy klucz API</h3>
            <button class="pjm-tr-modal-close">&times;</button>
        </div>
        <form id="pjm-api-form">
            <div class="ja-field">
                <label>Etykieta partnera <span class="req">*</span></label>
                <input type="text" name="label" id="api-label" placeholder="np. Sklep partnera XYZ" required>
            </div>

            <div class="ja-field">
                <label>Zakresy uprawnień <span class="req">*</span></label>
                <div class="ja-lang-grid" id="api-scopes">
                    <?php foreach ( $scope_labels as $code => $label ) : ?>
                        <label class="ja-lang-check">
                            <input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $code ); ?>">
                            <span><strong><?php echo esc_html( $code ); ?></strong> <?php echo esc_html( $label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ja-form-actions">
                <button type="submit" class="ja-btn ja-btn-primary"><span class="material-symbols-rounded">vpn_key</span> Utwórz klucz</button>
                <button type="button" class="ja-btn pjm-tr-modal-close">Anuluj</button>
            </div>
        </form>
    </div>
</div>

<style>
.ja-tr-header { display:flex; justify-content:space-between; align-items:flex-start; }
.ja-small { font-size:12px; }
.ja-btn-primary { background:#1B5E4B; color:#fff; border-color:#1B5E4B; }
.ja-btn-primary:hover { background:#154A3B; }
.ja-btn-primary .material-symbols-rounded { font-size:18px; margin-right:4px; }
.ja-icon-btn { border:none; background:#f1f3f5; width:32px; height:32px; border-radius:6px; cursor:pointer; color:#555; display:inline-flex; align-items:center; justify-content:center; margin-left:4px; }
.ja-icon-btn:hover { background:#fdecea; color:#e53935; }
.ja-icon-btn .material-symbols-rounded { font-size:18px; }

.ja-scope-chip { display:inline-block; background:#e3f2fd; color:#1565c0; font-size:11px; font-weight:600; padding:2px 6px; border-radius:4px; margin:1px; font-family:ui-monospace,monospace; }
.ja-api-prefix { background:#f1f3f5; padding:2px 6px; border-radius:4px; font-size:12px; }
.ja-api-badge { padding:3px 9px; border-radius:12px; font-size:11px; font-weight:600; }
.ja-api-active { background:#e6f4ea; color:#1e7e34; }
.ja-api-revoked { background:#fdecea; color:#c0392b; }

.ja-api-newkey { margin-top:18px; background:#fff8e1; border:1px solid #ffe082; border-radius:10px; padding:16px 18px; }
.ja-api-newkey-head { display:flex; align-items:center; gap:8px; font-weight:700; color:#b26a00; margin-bottom:10px; }
.ja-api-newkey-head .material-symbols-rounded { color:#f9a825; }
.ja-api-newkey-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ja-api-newkey-row code { flex:1; min-width:240px; background:#fff; border:1px dashed #ffca28; border-radius:8px; padding:10px 12px; font-size:14px; font-family:ui-monospace,monospace; word-break:break-all; color:#333; }

.pjm-tr-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:flex-start; justify-content:center; padding:40px 16px; overflow-y:auto; }
.pjm-tr-modal.open { display:flex; }
.pjm-tr-modal-box { background:#fff; border-radius:12px; width:100%; max-width:560px; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
.pjm-tr-modal-head { display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid #eee; }
.pjm-tr-modal-head h3 { margin:0; font-size:17px; }
.pjm-tr-modal-close { background:none; border:none; font-size:26px; cursor:pointer; color:#999; line-height:1; }
#pjm-api-form { padding:24px; }
.ja-field { margin-bottom:14px; }
.ja-field label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
.ja-field .req { color:#e53935; }
.ja-field input { width:100%; padding:9px 12px; border:1px solid #dfe6e9; border-radius:8px; font-size:14px; font-family:inherit; box-sizing:border-box; }
.ja-field input:focus { border-color:#1B5E4B; outline:none; box-shadow:0 0 0 3px rgba(27, 94, 75,0.12); }
.ja-lang-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:6px; }
.ja-lang-check { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:400; background:#f8f9fa; padding:6px 8px; border-radius:6px; cursor:pointer; }
.ja-lang-check input { width:auto; }
.ja-form-actions { display:flex; gap:10px; margin-top:8px; }
</style>

<script>
jQuery(function($){
    var $modal = $('#pjm-api-modal');

    function openModal(){ $modal.addClass('open'); }
    function closeModal(){ $modal.removeClass('open'); }

    $('#pjm-api-add').on('click', function(){
        $('#pjm-api-form')[0].reset();
        $('#api-scopes input').prop('checked', false);
        openModal();
    });

    $(document).on('click', '.pjm-tr-modal-close', closeModal);
    $modal.on('click', function(e){ if(e.target===this) closeModal(); });

    $('#pjm-api-form').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({name:'action', value:'pjm_admin_create_api_key'});
        data.push({name:'nonce', value:pjm_admin_vars.nonce});
        $.post(pjm_admin_vars.ajax_url, $.param(data), function(res){
            if(res.success){
                closeModal();
                $('#pjm-api-newkey-value').text(res.data.full_key);
                $('#pjm-api-newkey').show();
                $('html,body').animate({scrollTop: $('#pjm-api-newkey').offset().top - 40}, 300);
                alert(res.data.message + '\n\n' + res.data.full_key);
            } else {
                alert('Błąd: ' + res.data);
            }
        });
    });

    $('#pjm-api-copy').on('click', function(){
        var val = $('#pjm-api-newkey-value').text();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(val).then(function(){
                $('#pjm-api-copy').html('<span class="material-symbols-rounded">check</span> Skopiowano');
            });
        } else {
            var tmp = $('<textarea>').val(val).appendTo('body').select();
            document.execCommand('copy');
            tmp.remove();
            $('#pjm-api-copy').html('<span class="material-symbols-rounded">check</span> Skopiowano');
        }
    });

    $(document).on('click', '.pjm-api-revoke', function(){
        var id = $(this).data('id'), label = $(this).data('label');
        if(!confirm('Odwołać klucz "'+label+'"? Partner straci dostęp natychmiast. Tej operacji nie można cofnąć.')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_revoke_api_key', id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success) location.reload(); else alert('Błąd: ' + res.data);
        });
    });
});
</script>
