<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$products = pjm_get_products( false );

$type_labels = [
    'service' => 'Usługa',
    'digital' => 'Produkt cyfrowy',
    'bundle'  => 'Pakiet',
];
$deliverable_labels = [
    'file'          => 'Plik do pobrania',
    'external_link' => 'Link zewnętrzny',
    'license_key'   => 'Klucz licencyjny',
];
$vat_labels = [
    '23' => '23%',
    '8'  => '8%',
    '5'  => '5%',
    '0'  => '0%',
    'zw' => 'zw.',
];
?>

<div class="ja-products-panel">

    <div class="ja-pr-header">
        <div>
            <h2 style="margin:0; font-size:18px;">
                <span class="material-symbols-rounded" style="vertical-align:middle; color:#1B5E4B;">storefront</span>
                Katalog produktów
            </h2>
            
        </div>
        <button class="ja-btn ja-btn-primary" id="pjm-pr-add">
            <span class="material-symbols-rounded">add_box</span> Dodaj produkt
        </button>
    </div>

    <div class="ja-card table-card" style="margin-top:18px;">
        <table class="ja-table">
            <thead>
                <tr>
                    <th>Nazwa / SKU</th>
                    <th>Typ</th>
                    <th>Cena</th>
                    <th>VAT</th>
                    <th>Status</th>
                    <th style="text-align:right;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $products ) ) : ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#777;">Brak produktów. Dodaj pierwszy, aby udostępnić go w sklepie.</td></tr>
                <?php endif; ?>
                <?php foreach ( $products as $p ) : ?>
                <tr<?php echo $p->is_active ? '' : ' style="opacity:0.55;"'; ?>>
                    <td>
                        <strong><?php echo esc_html( $p->name ); ?></strong>
                        <?php if ( $p->sku ) : ?><div class="ja-small" style="color:#999;"><?php echo esc_html( $p->sku ); ?></div><?php endif; ?>
                    </td>
                    <td><span class="ja-pill"><?php echo esc_html( $type_labels[ $p->type ] ?? $p->type ); ?></span></td>
                    <td><?php echo number_format( (float) $p->price, 2, ',', ' ' ); ?> <?php echo esc_html( $p->currency ?: 'PLN' ); ?></td>
                    <td><?php echo esc_html( $vat_labels[ $p->vat_rate ] ?? $p->vat_rate ); ?></td>
                    <td>
                        <?php if ( $p->is_active ) : ?>
                            <span class="ja-status ja-status-on">Aktywny</span>
                        <?php else : ?>
                            <span class="ja-status ja-status-off">Nieaktywny</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <button class="ja-icon-btn pjm-pr-edit" data-id="<?php echo (int) $p->id; ?>" title="Edytuj">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                        <?php if ( $p->is_active ) : ?>
                        <button class="ja-icon-btn pjm-pr-delete" data-id="<?php echo (int) $p->id; ?>" data-name="<?php echo esc_attr( $p->name ); ?>" title="Dezaktywuj">
                            <span class="material-symbols-rounded">visibility_off</span>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: dodaj/edytuj produkt -->
<div class="pjm-tr-modal" id="pjm-pr-modal">
    <div class="pjm-tr-modal-box">
        <div class="pjm-tr-modal-head">
            <h3 id="pjm-pr-modal-title">Nowy produkt</h3>
            <button class="pjm-pr-modal-close">&times;</button>
        </div>
        <form id="pjm-pr-form">
            <input type="hidden" name="id" id="pr-id" value="">

            <div class="ja-field-row">
                <div class="ja-field">
                    <label>Nazwa <span class="req">*</span></label>
                    <input type="text" name="name" id="pr-name" required>
                </div>
                <div class="ja-field">
                    <label>SKU</label>
                    <input type="text" name="sku" id="pr-sku" placeholder="np. PJM-KURS-01">
                </div>
            </div>

            <div class="ja-field-row ja-field-row-3">
                <div class="ja-field">
                    <label>Typ</label>
                    <select name="type" id="pr-type">
                        <?php foreach ( $type_labels as $k => $v ) : ?>
                            <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Cena (brutto)</label>
                    <input type="number" name="price" id="pr-price" step="0.01" min="0" value="0">
                </div>
                <div class="ja-field">
                    <label>VAT</label>
                    <select name="vat_rate" id="pr-vat">
                        <option value="23">23%</option>
                        <option value="8">8%</option>
                        <option value="5">5%</option>
                        <option value="0">0%</option>
                        <option value="zw">zw.</option>
                    </select>
                </div>
            </div>

            <div class="ja-field-row ja-field-row-3">
                <div class="ja-field">
                    <label>Status</label>
                    <select name="is_active" id="pr-active">
                        <option value="1">Aktywny</option>
                        <option value="0">Nieaktywny</option>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Sposób dostarczenia</label>
                    <select name="deliverable_type" id="pr-deliv">
                        <?php foreach ( $deliverable_labels as $k => $v ) : ?>
                            <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Stan mag. (puste = bez limitu)</label>
                    <input type="number" name="stock_qty" id="pr-stock" step="1" min="0" placeholder="∞">
                </div>
            </div>

            <div class="ja-field">
                <label>Ścieżka pliku (w katalogu chronionym)</label>
                <input type="text" name="deliverable_path" id="pr-path" placeholder="np. /protected/kurs-pjm.zip — plik wgrywasz osobno">
            </div>

            <div class="ja-field">
                <label>Link zewnętrzny (dla typu „link zewnętrzny”)</label>
                <input type="text" name="external_url" id="pr-exturl" placeholder="https://…">
            </div>

            <div class="ja-field">
                <label>URL obrazka</label>
                <input type="text" name="image_url" id="pr-image" placeholder="https://…">
            </div>

            <div class="ja-field">
                <label>Krótki opis</label>
                <textarea name="short_desc" id="pr-short" rows="2"></textarea>
            </div>

            <div class="ja-field">
                <label>Opis pełny</label>
                <textarea name="description" id="pr-desc" rows="4"></textarea>
            </div>

            <div class="ja-form-actions">
                <button type="submit" class="ja-btn ja-btn-primary"><span class="material-symbols-rounded">save</span> Zapisz</button>
                <button type="button" class="ja-btn pjm-pr-modal-close">Anuluj</button>
            </div>
        </form>
    </div>
</div>

<style>
.ja-pr-header { display:flex; justify-content:space-between; align-items:flex-start; }
.ja-small { font-size:12px; }
.ja-pill { background:#eef2f7; color:#445; padding:3px 8px; border-radius:12px; font-size:11px; }
.ja-status { font-size:11px; font-weight:600; padding:3px 8px; border-radius:12px; }
.ja-status-on { background:#e6f4ea; color:#1e7e34; }
.ja-status-off { background:#f1f3f5; color:#868e96; }
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
.pjm-pr-modal-close { background:none; border:none; font-size:26px; cursor:pointer; color:#999; line-height:1; }
#pjm-pr-form { padding:24px; }
.ja-field { margin-bottom:14px; }
.ja-field label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
.ja-field .req { color:#e53935; }
.ja-field input, .ja-field select, .ja-field textarea { width:100%; padding:9px 12px; border:1px solid #dfe6e9; border-radius:8px; font-size:14px; font-family:inherit; box-sizing:border-box; }
.ja-field input:focus, .ja-field select:focus, .ja-field textarea:focus { border-color:#1B5E4B; outline:none; box-shadow:0 0 0 3px rgba(27, 94, 75,0.12); }
.ja-field-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.ja-field-row-3 { grid-template-columns:1fr 1fr 1fr; }
@media (max-width:600px){ .ja-field-row, .ja-field-row-3 { grid-template-columns:1fr; } }
.ja-form-actions { display:flex; gap:10px; margin-top:8px; }
</style>

<script>
jQuery(function($){
    var $modal = $('#pjm-pr-modal');

    function openModal(title){ $('#pjm-pr-modal-title').text(title); $modal.addClass('open'); }
    function closeModal(){ $modal.removeClass('open'); }

    $('#pjm-pr-add').on('click', function(){
        $('#pjm-pr-form')[0].reset();
        $('#pr-id').val('');
        openModal('Nowy produkt');
    });

    $(document).on('click', '.pjm-pr-modal-close', closeModal);
    $modal.on('click', function(e){ if(e.target===this) closeModal(); });

    $(document).on('click', '.pjm-pr-edit', function(){
        var id = $(this).data('id');
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_get_product', product_id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(!res.success){ alert('Błąd: '+res.data); return; }
            var p = res.data;
            $('#pr-id').val(p.id);
            $('#pr-name').val(p.name);
            $('#pr-sku').val(p.sku);
            $('#pr-type').val(p.type);
            $('#pr-price').val(p.price);
            $('#pr-vat').val(p.vat_rate);
            $('#pr-active').val(String(p.is_active));
            $('#pr-deliv').val(p.deliverable_type);
            $('#pr-stock').val(p.stock_qty === null ? '' : p.stock_qty);
            $('#pr-path').val(p.deliverable_path);
            $('#pr-exturl').val(p.external_url);
            $('#pr-image').val(p.image_url);
            $('#pr-short').val(p.short_desc);
            $('#pr-desc').val(p.description);
            openModal('Edytuj: '+p.name);
        });
    });

    $('#pjm-pr-form').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({name:'action', value:'pjm_admin_save_product'});
        data.push({name:'nonce', value:pjm_admin_vars.nonce});
        $.post(pjm_admin_vars.ajax_url, $.param(data), function(res){
            if(res.success){ closeModal(); location.reload(); }
            else alert('Błąd: '+res.data);
        });
    });

    $(document).on('click', '.pjm-pr-delete', function(){
        var id=$(this).data('id'), name=$(this).data('name');
        if(!confirm('Dezaktywować produkt "'+name+'"? Pozostanie w bazie (historia zamówień).')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_delete_product', product_id:id, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success) location.reload(); else alert('Błąd: '+res.data);
        });
    });
});
</script>
