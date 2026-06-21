<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$translators = pjm_get_translators( 'active' );
$lang_codes  = pjm_get_language_codes();

$contract_labels = [
    'zlecenie' => 'Umowa zlecenie',
    'dzielo'   => 'Umowa o dzieło',
    'b2b'      => 'B2B / faktura',
    'wew'      => 'Wewnętrzny',
];
$unit_labels = [
    'hour'    => 'godzina',
    'minute'  => 'minuta',
    'page'    => 'strona',
    'project' => 'projekt',
];
?>

<div class="ja-translators-panel">

    <div class="ja-tr-header">
        <div>
            <h2 style="margin:0; font-size:18px;">
                <span class="material-symbols-rounded" style="vertical-align:middle; color:#1B5E4B;">interpreter_mode</span>
                Tłumacze / podwykonawcy
            </h2>
            <p style="margin:4px 0 0; color:var(--ja-text-light); font-size:13px;">Kartoteka osób realizujących zlecenia. Podstawa rozliczeń miesięcznych.</p>
        </div>
        <button class="ja-btn ja-btn-primary" id="pjm-tr-add">
            <span class="material-symbols-rounded">person_add</span> Dodaj tłumacza
        </button>
    </div>

    <div class="ja-card table-card" style="margin-top:18px;">
        <table class="ja-table">
            <thead>
                <tr>
                    <th>Tłumacz</th>
                    <th>Kontakt</th>
                    <th>Umowa</th>
                    <th>Stawka domyślna</th>
                    <th>Języki</th>
                    <th style="text-align:right;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $translators ) ) : ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#777;">Brak tłumaczy. Dodaj pierwszego, aby przypisywać go do zleceń.</td></tr>
                <?php endif; ?>
                <?php foreach ( $translators as $t ) :
                    $langs = json_decode( $t->lang_codes_json, true ) ?: [];
                ?>
                <tr>
                    <td><strong><?php echo esc_html( $t->display_name ); ?></strong></td>
                    <td>
                        <?php if ( $t->email ) : ?><div class="ja-small"><?php echo esc_html( $t->email ); ?></div><?php endif; ?>
                        <?php if ( $t->phone ) : ?><div class="ja-small" style="color:#999;"><?php echo esc_html( $t->phone ); ?></div><?php endif; ?>
                    </td>
                    <td><span class="ja-pill"><?php echo esc_html( $contract_labels[ $t->contract_type ] ?? $t->contract_type ); ?></span></td>
                    <td><?php echo number_format( (float) $t->default_rate, 2, ',', ' ' ); ?> zł / <?php echo esc_html( $unit_labels[ $t->rate_unit ] ?? $t->rate_unit ); ?></td>
                    <td>
                        <?php if ( empty( $langs ) ) : ?>
                            <span style="color:#bbb;">—</span>
                        <?php else : foreach ( array_slice( $langs, 0, 5 ) as $lc ) : ?>
                            <span class="ja-lang-chip"><?php echo esc_html( $lc ); ?></span>
                        <?php endforeach; endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <button class="ja-icon-btn pjm-tr-edit" data-id="<?php echo $t->id; ?>" title="Edytuj">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                        <button class="ja-icon-btn pjm-tr-archive" data-id="<?php echo $t->id; ?>" data-name="<?php echo esc_attr( $t->display_name ); ?>" title="Archiwizuj">
                            <span class="material-symbols-rounded">archive</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: dodaj/edytuj tłumacza -->
<div class="pjm-tr-modal" id="pjm-tr-modal">
    <div class="pjm-tr-modal-box">
        <div class="pjm-tr-modal-head">
            <h3 id="pjm-tr-modal-title">Nowy tłumacz</h3>
            <button class="pjm-tr-modal-close">&times;</button>
        </div>
        <form id="pjm-tr-form">
            <input type="hidden" name="id" id="tr-id" value="">

            <div class="ja-field-row">
                <div class="ja-field">
                    <label>Imię i nazwisko <span class="req">*</span></label>
                    <input type="text" name="display_name" id="tr-name" required>
                </div>
                <div class="ja-field">
                    <label>Status</label>
                    <select name="status" id="tr-status">
                        <option value="active">Aktywny</option>
                        <option value="inactive">Nieaktywny</option>
                    </select>
                </div>
            </div>

            <div class="ja-field-row">
                <div class="ja-field">
                    <label>E-mail</label>
                    <input type="email" name="email" id="tr-email" placeholder="do wysyłki briefów i rozliczeń">
                </div>
                <div class="ja-field">
                    <label>Telefon</label>
                    <input type="text" name="phone" id="tr-phone">
                </div>
            </div>

            <div class="ja-field">
                <label>Powiązane konto WP <span style="color:#888; font-weight:400;">(umożliwia logowanie do panelu tłumacza)</span></label>
                <select name="user_id" id="tr-user">
                    <option value="">— brak —</option>
                    <?php foreach ( get_users( [ 'orderby' => 'display_name', 'number' => 300 ] ) as $wpu ) : ?>
                        <option value="<?php echo (int) $wpu->ID; ?>"><?php echo esc_html( $wpu->display_name . ' (' . $wpu->user_email . ')' ); ?></option>
                    <?php endforeach; ?>
                </select>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px; font-weight:400; font-size:13px; color:#42514a;">
                    <input type="checkbox" name="create_account" id="tr-create" value="1" style="width:auto;">
                    Utwórz konto tłumacza i wyślij zaproszenie e-mail (link do ustawienia hasła) — gdy brak powiązanego konta, a podany jest e-mail
                </label>
            </div>

            <div class="ja-field-row ja-field-row-3">
                <div class="ja-field">
                    <label>Typ umowy</label>
                    <select name="contract_type" id="tr-contract">
                        <?php foreach ( $contract_labels as $k => $v ) : ?>
                            <option value="<?php echo $k; ?>"><?php echo esc_html( $v ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Stawka domyślna (zł)</label>
                    <input type="number" name="default_rate" id="tr-rate" step="0.01" min="0" value="0">
                </div>
                <div class="ja-field">
                    <label>Netto / brutto</label>
                    <select name="rate_tax" id="tr-ratetax">
                        <option value="netto">netto</option>
                        <option value="brutto">brutto</option>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Jednostka</label>
                    <select name="rate_unit" id="tr-unit">
                        <?php foreach ( $unit_labels as $k => $v ) : ?>
                            <option value="<?php echo $k; ?>"><?php echo esc_html( $v ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ja-field">
                <label>Obsługiwane języki / kody</label>
                <div class="ja-lang-grid" id="tr-langs">
                    <?php foreach ( $lang_codes as $code => $info ) : ?>
                        <label class="ja-lang-check">
                            <input type="checkbox" name="lang_codes[]" value="<?php echo esc_attr( $code ); ?>">
                            <span><strong><?php echo esc_html( $code ); ?></strong> <?php echo esc_html( $info['label'] ?? '' ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <details class="ja-billing-details">
                <summary>Dane do rozliczeń (opcjonalne)</summary>
                <p class="ja-small" style="color:#999;">Minimum danych. NIE wpisuj tu numeru PESEL ani numeru konta — te dane przekaż bezpośrednio księgowej.</p>
                <div class="ja-field-row ja-field-row-3">
                    <div class="ja-field"><label>Firma (jeśli B2B)</label><input type="text" name="billing_company" id="tr-bcompany"></div>
                    <div class="ja-field"><label>NIP</label><input type="text" name="billing_nip" id="tr-bnip"></div>
                    <div class="ja-field"><label>Miasto</label><input type="text" name="billing_address" id="tr-baddress"></div>
                </div>
            </details>

            <div class="ja-field">
                <label>Notatki (specjalizacja, np. medyczne / prawnicze)</label>
                <textarea name="admin_notes" id="tr-notes" rows="2"></textarea>
            </div>

            <div class="ja-form-actions">
                <button type="submit" class="ja-btn ja-btn-primary"><span class="material-symbols-rounded">save</span> Zapisz</button>
                <button type="button" class="ja-btn pjm-tr-modal-close">Anuluj</button>
            </div>
        </form>
    </div>
</div>

<style>
.ja-tr-header { display:flex; justify-content:space-between; align-items:flex-start; }
.ja-small { font-size:12px; }
.ja-pill { background:#eef2f7; color:#445; padding:3px 8px; border-radius:12px; font-size:11px; }
.ja-lang-chip { display:inline-block; background:#FBF1DC; color:#e8590c; font-size:11px; font-weight:600; padding:2px 6px; border-radius:4px; margin:1px; }
.ja-btn-primary { background:#1B5E4B; color:#fff; border-color:#1B5E4B; }
.ja-btn-primary:hover { background:#154A3B; }
.ja-btn-primary .material-symbols-rounded { font-size:18px; margin-right:4px; }
.ja-icon-btn { border:none; background:#f1f3f5; width:32px; height:32px; border-radius:6px; cursor:pointer; color:#555; display:inline-flex; align-items:center; justify-content:center; margin-left:4px; }
.ja-icon-btn:hover { background:#e9ecef; color:#1B5E4B; }
.ja-icon-btn .material-symbols-rounded { font-size:18px; }

.pjm-tr-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:flex-start; justify-content:center; padding:40px 16px; overflow-y:auto; }
.pjm-tr-modal.open { display:flex; }
.pjm-tr-modal-box { background:#fff; border-radius:12px; width:100%; max-width:640px; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
.pjm-tr-modal-head { display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid #eee; }
.pjm-tr-modal-head h3 { margin:0; font-size:17px; }
.pjm-tr-modal-close { background:none; border:none; font-size:26px; cursor:pointer; color:#999; line-height:1; }
#pjm-tr-form { padding:24px; }
.ja-field { margin-bottom:14px; }
.ja-field label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
.ja-field .req { color:#e53935; }
.ja-field input, .ja-field select, .ja-field textarea { width:100%; padding:9px 12px; border:1px solid #dfe6e9; border-radius:8px; font-size:14px; font-family:inherit; box-sizing:border-box; }
.ja-field input:focus, .ja-field select:focus, .ja-field textarea:focus { border-color:#1B5E4B; outline:none; box-shadow:0 0 0 3px rgba(27, 94, 75,0.12); }
.ja-field-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.ja-field-row-3 { grid-template-columns:1fr 1fr 1fr; }
@media (max-width:600px){ .ja-field-row, .ja-field-row-3 { grid-template-columns:1fr; } }
.ja-lang-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr)); gap:6px; }
.ja-lang-check { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:400; background:#f8f9fa; padding:6px 8px; border-radius:6px; cursor:pointer; }
.ja-lang-check input { width:auto; }
.ja-billing-details { margin:6px 0 14px; background:#f8f9fa; border-radius:8px; padding:10px 14px; }
.ja-billing-details summary { cursor:pointer; font-weight:600; font-size:13px; }
.ja-form-actions { display:flex; gap:10px; margin-top:8px; }
</style>

<script>
jQuery(function($){
    var $modal = $('#pjm-tr-modal');

    function openModal(title){ $('#pjm-tr-modal-title').text(title); $modal.addClass('open'); }
    function closeModal(){ $modal.removeClass('open'); }

    $('#pjm-tr-add').on('click', function(){
        $('#pjm-tr-form')[0].reset();
        $('#tr-id').val('');
        $('#tr-langs input').prop('checked', false);
        openModal('Nowy tłumacz');
    });

    $(document).on('click', '.pjm-tr-modal-close', closeModal);
    $modal.on('click', function(e){ if(e.target===this) closeModal(); });

    $(document).on('click', '.pjm-tr-edit', function(){
        var id = $(this).data('id');
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_get_translator', translator_id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(!res.success){ alert('Błąd: '+res.data); return; }
            var t = res.data;
            $('#tr-id').val(t.id);
            $('#tr-name').val(t.display_name);
            $('#tr-status').val(t.status);
            $('#tr-user').val(t.user_id || '');
            $('#tr-email').val(t.email);
            $('#tr-phone').val(t.phone);
            $('#tr-contract').val(t.contract_type);
            $('#tr-rate').val(t.default_rate);
            $('#tr-ratetax').val(t.rate_tax || 'netto');
            $('#tr-unit').val(t.rate_unit);
            $('#tr-notes').val(t.admin_notes);
            $('#tr-bcompany').val((t.billing||{}).company||'');
            $('#tr-bnip').val((t.billing||{}).nip||'');
            $('#tr-baddress').val((t.billing||{}).address||'');
            $('#tr-langs input').prop('checked', false);
            (t.lang_codes||[]).forEach(function(c){ $('#tr-langs input[value="'+c+'"]').prop('checked', true); });
            openModal('Edytuj: '+t.display_name);
        });
    });

    $('#pjm-tr-form').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({name:'action', value:'pjm_admin_save_translator'});
        data.push({name:'nonce', value:pjm_admin_vars.nonce});
        $.post(pjm_admin_vars.ajax_url, $.param(data), function(res){
            if(res.success){ closeModal(); location.reload(); }
            else alert('Błąd: '+res.data);
        });
    });

    $(document).on('click', '.pjm-tr-archive', function(){
        var id=$(this).data('id'), name=$(this).data('name');
        if(!confirm('Zarchiwizować tłumacza "'+name+'"? Historia rozliczeń zostanie zachowana.')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_archive_translator', translator_id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success) location.reload(); else alert('Błąd: '+res.data);
        });
    });
});
</script>
