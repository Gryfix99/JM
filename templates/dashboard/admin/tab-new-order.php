<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$service_types = [
    'video'        => 'Tłumaczenie nagrań wideo',
    'text'         => 'Tłumaczenie tekstów',
    'event'        => 'Tłumaczenie na żywo (wydarzenie)',
    'loop'         => 'Pętla indukcyjna',
    'subscription' => 'Abonament',
    'other'        => 'Inne / niestandardowe',
];
$status_options = [
    'new'             => 'Nowe',
    'pending_payment' => 'Oczekuje na płatność',
    'in_progress'     => 'W realizacji',
    'completed'       => 'Zrealizowane',
    'cancelled'       => 'Anulowane',
];
// Metoda płatności. „faktura_po" = faktura przelewowa: realizujemy, płatność PO realizacji.
$payment_methods = function_exists('pjm_payment_method_labels') ? [
    'faktura_po' => 'Faktura przelewowa',
    'proforma'   => 'Proforma',
    'online'     => 'Płatność online',
    'transfer'   => 'Przelew',
    'cash'       => 'Gotówka',
] : [ 'proforma' => 'Proforma' ];
$payment_status_options = [
    'unpaid'   => 'Nieopłacone',
    'deferred' => 'Płatność odroczona',
    'proforma' => 'Proforma wystawiona',
    'paid'     => 'Opłacone',
    'overdue'  => 'Po terminie płatności',
];
$delivery_modes = [
    'standard'    => 'Standard',
    'express_48h' => 'Ekspres 48h (+20%)',
    'express_24h' => 'Ekspres 24h (+40%)',
];

$users        = get_users(['orderby' => 'display_name', 'order' => 'ASC', 'number' => 300]);
$translators  = function_exists('pjm_get_translators') ? pjm_get_translators('active') : [];
$lang_codes   = function_exists('pjm_get_language_codes') ? pjm_get_language_codes() : [];
$role_labels  = [
    'translator' => 'Tłumacz',
    'technician' => 'Obsługa techniczna',
];
?>

<style>
/* Nowoczesny, minimalistyczny formularz nowego zamówienia */
.ja-new-order-form { max-width: 860px; margin: 0 auto; font-family:'Inter',system-ui,sans-serif; }
.ja-form-header { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
.ja-form-header h2 { font-family:'Plus Jakarta Sans',sans-serif; color:#16241D; }
.ja-form-section { background:#fff; border:1px solid #e8ece9; border-radius:16px; padding:22px 24px; margin-bottom:18px; }
.ja-form-section > h3 { display:flex; align-items:center; gap:8px; margin:0 0 18px; font-size:15px; color:#16241D; font-family:'Plus Jakarta Sans',sans-serif; }
.ja-form-section > h3 .material-symbols-rounded { color:#1B5E4B; font-size:20px; }
.ja-field { margin-bottom:14px; }
.ja-field-row { display:flex; gap:14px; flex-wrap:wrap; }
.ja-field-row .ja-field { flex:1; min-width:160px; }
.ja-field label { display:block; font-size:12px; font-weight:600; color:#5f6f67; margin-bottom:6px; }
.ja-field .req { color:#C0392B; }
.ja-new-order-form input, .ja-new-order-form select, .ja-new-order-form textarea {
    width:100%; box-sizing:border-box; padding:11px 13px; border:1px solid #d8ded9; border-radius:10px;
    font-size:14px; color:#16241D; background:#fff; font-family:inherit; transition:border-color .15s, box-shadow .15s;
}
.ja-new-order-form input:hover, .ja-new-order-form select:hover { border-color:#b9c6bf; }
.ja-new-order-form input:focus, .ja-new-order-form select:focus, .ja-new-order-form textarea:focus {
    outline:none; border-color:#1B5E4B; box-shadow:0 0 0 3px rgba(27,94,75,.12);
}
.ja-new-order-form input[type="date"], .ja-new-order-form input[type="datetime-local"], .ja-new-order-form input[type="time"] { cursor:pointer; }
.ja-new-order-form input[type="date"]::-webkit-calendar-picker-indicator,
.ja-new-order-form input[type="datetime-local"]::-webkit-calendar-picker-indicator,
.ja-new-order-form input[type="time"]::-webkit-calendar-picker-indicator { cursor:pointer; opacity:.6; filter:invert(28%) sepia(20%) saturate(900%) hue-rotate(110deg); }
.pjm-svc-fields { background:#f7faf8; border:1px solid #e3e1d9; border-radius:12px; padding:16px; margin-top:6px; }
.pjm-eday { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.pjm-eday input[type=date]{ flex:1.5; } .pjm-eday input[type=time]{ flex:1; }
.pjm-eday-sep { color:#8A938D; }
.pjm-eday-del { border:none; background:#fee2e2; color:#b91c1c; border-radius:8px; width:38px; height:38px; cursor:pointer; font-size:18px; line-height:1; flex-shrink:0; }
.pjm-eday-del:hover { background:#fecaca; }
#pjm-event-add-day { background:#fff; border:1px dashed #1B5E4B; color:#1B5E4B; border-radius:10px; padding:8px 14px; cursor:pointer; font-weight:600; }
.ja-form-actions, .ja-new-order-form .ja-actions { margin-top:8px; }
#pjm-mo-submit { background:#1B5E4B; color:#fff; border:none; border-radius:12px; padding:13px 28px; font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
#pjm-mo-submit:hover { background:#154A3B; }
#pjm-mo-submit:disabled { opacity:.6; cursor:default; }
@media(max-width:560px){ .ja-form-section{ padding:18px 16px; } }
</style>

<div class="ja-new-order-form">

    <div class="ja-form-header">
        <span class="material-symbols-rounded" style="font-size:28px; color:var(--pjm-primary,#1B5E4B);">post_add</span>
        <div>
            <h2 style="margin:0; font-size:20px;">Nowe zamówienie ręczne</h2>
            
        </div>
    </div>

    <form id="pjm-manual-order-form" autocomplete="off">

        <!-- KLIENT -->
        <div class="ja-form-section">
            <h3><span class="material-symbols-rounded">person</span> Klient</h3>
            <div class="ja-field">
                <label>Wybierz klienta <span class="req">*</span></label>
                <select id="pjm-mo-client" name="user_id" required>
                    <option value="">— Wybierz klienta —</option>
                    <?php foreach ( $users as $u ) : ?>
                        <option value="<?php echo $u->ID; ?>"><?php echo esc_html( $u->display_name . ' (' . $u->user_email . ')' ); ?></option>
                    <?php endforeach; ?>
                    <option value="new">+ Nowy klient (podaj dane poniżej)</option>
                </select>
            </div>
            <div id="pjm-mo-new-client" style="display:none;">
                <div class="ja-field-row">
                    <div class="ja-field"><label>Imię i nazwisko</label><input type="text" name="new_name" placeholder="Jan Kowalski"></div>
                    <div class="ja-field"><label>E-mail</label><input type="email" name="new_email" placeholder="jan@firma.pl"></div>
                </div>
                <div class="ja-field"><label>Telefon</label><input type="text" name="new_phone" placeholder="+48 600 000 000"></div>

                <div class="ja-field">
                    <label>Typ klienta</label>
                    <select name="new_client_type" id="pjm-mo-new-ctype">
                        <option value="company">Firma (do faktury)</option>
                        <option value="private">Osoba prywatna</option>
                    </select>
                </div>
                <div id="pjm-mo-company-fields">
                    <p style="font-size:12px;color:#6b7280;margin:4px 0 10px;">Dane do faktury — zapiszą się w profilu klienta.</p>
                    <div class="ja-field-row">
                        <div class="ja-field"><label>Nazwa firmy</label><input type="text" name="new_company" placeholder="Firma Sp. z o.o."></div>
                        <div class="ja-field"><label>NIP</label><input type="text" name="new_nip" placeholder="1234567890" inputmode="numeric"></div>
                    </div>
                    <div class="ja-field"><label>Ulica i numer</label><input type="text" name="new_street" placeholder="ul. Przykładowa 1"></div>
                    <div class="ja-field-row">
                        <div class="ja-field"><label>Kod pocztowy</label><input type="text" name="new_zip" placeholder="00-000"></div>
                        <div class="ja-field"><label>Miasto</label><input type="text" name="new_city" placeholder="Warszawa"></div>
                    </div>
                    <div class="ja-field"><label>E-mail do faktur</label><input type="email" name="new_billing_email" placeholder="ksiegowosc@firma.pl (jeśli inny niż konto)"></div>
                </div>
            </div>
        </div>

        <!-- USŁUGA -->
        <div class="ja-form-section">
            <h3><span class="material-symbols-rounded">category</span> Usługa</h3>
            <div class="ja-field-row">
                <div class="ja-field">
                    <label>Typ usługi <span class="req">*</span></label>
                    <select name="service_type" id="pjm-mo-service" required>
                        <?php foreach ( $service_types as $k => $v ) : ?>
                            <option value="<?php echo $k; ?>"><?php echo esc_html( $v ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Data zamówienia</label>
                    <input type="date" name="order_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                </div>
            </div>
            <div class="ja-field-row pjm-deadline-row" style="display:none;">
                <div class="ja-field" style="flex:1;">
                    <label>Termin realizacji <small style="color:#9ca3af; font-weight:400;">— do kiedy zrealizować (teksty / wideo)</small></label>
                    <input type="datetime-local" name="deadline">
                </div>
            </div>

            <!-- JĘZYKI ZLECENIA (na poziomie zamówienia — tłumaczenie bywa dwukierunkowe) -->
            <?php $pjm_lc = function_exists( 'pjm_get_language_codes' ) ? pjm_get_language_codes() : []; ?>
            <div class="ja-field-row">
                <div class="ja-field">
                    <label>Język A <small style="color:#9ca3af; font-weight:400;">— języki zlecenia (A ↔ B)</small></label>
                    <select name="order_lang_from">
                        <option value="">—</option>
                        <?php foreach ( $pjm_lc as $code => $info ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( 'PL', $code ); ?>><?php echo esc_html( $code . ' — ' . ( $info['label'] ?? '' ) ); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="ja-field">
                    <label>Język B</label>
                    <select name="order_lang_to">
                        <option value="">—</option>
                        <?php foreach ( $pjm_lc as $code => $info ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( 'PJM', $code ); ?>><?php echo esc_html( $code . ' — ' . ( $info['label'] ?? '' ) ); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- WIDEO -->
            <div class="pjm-svc-fields" data-svc="video">
                <div class="ja-field-row ja-field-row-3">
                    <div class="ja-field"><label>Liczba filmów</label><input type="number" name="video_films" min="1" value="1" data-quote></div>
                    <div class="ja-field"><label>Długość 1 filmu (min)</label><input type="number" name="video_minutes" min="1" value="1" data-quote></div>
                    <div class="ja-field"><label>Tryb realizacji</label>
                        <select name="video_delivery" data-quote>
                            <?php foreach ( $delivery_modes as $k => $v ) : ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TEKST -->
            <div class="pjm-svc-fields" data-svc="text" style="display:none;">
                <div class="ja-field-row">
                    <div class="ja-field"><label>Liczba znaków (1 strona = 1800 zn.)</label><input type="number" name="text_chars" min="1" value="1800" data-quote></div>
                    <div class="ja-field"><label>Tryb realizacji</label>
                        <select name="text_delivery" data-quote>
                            <?php foreach ( $delivery_modes as $k => $v ) : ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- WYDARZENIE (wiele dni, każdy z własnymi godzinami) -->
            <div class="pjm-svc-fields" data-svc="event" style="display:none;">
                <label style="font-size:12px; font-weight:600; color:#5f6f67;">Terminy i godziny — każdy dzień osobno (godziny obecności tłumacza)</label>
                <div id="pjm-event-days">
                    <div class="pjm-eday">
                        <input type="date" name="event_day[]" data-quote>
                        <input type="time" name="event_from[]" value="09:00" data-quote>
                        <span class="pjm-eday-sep">–</span>
                        <input type="time" name="event_to[]" value="17:00" data-quote>
                        <button type="button" class="pjm-eday-del" title="Usuń dzień">×</button>
                    </div>
                </div>
                <button type="button" id="pjm-event-add-day" class="button" style="margin-top:8px;">+ Dodaj kolejny dzień</button>
                <div class="ja-field" style="margin-top:14px;">
                    <label>Lokalizacja</label>
                    <select name="event_location" data-quote>
                        <option value="stacjonarnie">Stacjonarnie</option>
                        <option value="online">Online</option>
                    </select>
                </div>
            </div>

            <!-- PĘTLA -->
            <div class="pjm-svc-fields" data-svc="loop" style="display:none;">
                <div class="ja-field-row">
                    <div class="ja-field"><label>Data od</label><input type="date" name="loop_from" data-quote></div>
                    <div class="ja-field"><label>Data do</label><input type="date" name="loop_to" data-quote></div>
                </div>
            </div>

            <!-- INNE / ABONAMENT -->
            <div class="pjm-svc-fields" data-svc="other" style="display:none;">
                <p class="ja-small" style="color:#888;">Dla usług niestandardowych podaj tytuł i kwotę ręcznie poniżej.</p>
            </div>
            <div class="pjm-svc-fields" data-svc="subscription" style="display:none;">
                <p class="ja-small" style="color:#888;">Abonament — podaj tytuł planu i kwotę ręcznie poniżej.</p>
            </div>

            <div class="ja-field">
                <label>Tytuł / opis zlecenia <span class="req">*</span></label>
                <input type="text" name="title" id="pjm-mo-title" placeholder="np. Tłumaczenie filmu 15 min dla Urzędu Miasta" required>
            </div>
            <!-- GDZIE BĘDZIE ZLECENIE — tryb (stacjonarnie/online) + odpowiednie pole. Trafia do tłumacza w mailu. -->
            <div class="ja-field">
                <label>Gdzie będzie zlecenie</label>
                <div class="pjm-mode-toggle">
                    <button type="button" class="pjm-mode-btn is-active" data-mode="stacjonarnie"><span class="material-symbols-rounded">apartment</span> Stacjonarnie</button>
                    <button type="button" class="pjm-mode-btn" data-mode="online"><span class="material-symbols-rounded">videocam</span> Online</button>
                </div>
                <input type="hidden" name="order_mode" id="pjm-order-mode" value="stacjonarnie">
            </div>
            <div class="ja-field pjm-mode-field" data-mode="stacjonarnie">
                <label>Adres</label>
                <input type="text" name="order_location" placeholder="np. ul. Marszałkowska 1, Warszawa">
            </div>
            <div class="ja-field pjm-mode-field" data-mode="online" style="display:none;">
                <label>Link do spotkania <small style="color:#9ca3af; font-weight:400;">— Zoom / Teams / Meet</small></label>
                <input type="url" name="order_meeting_url" placeholder="https://...">
            </div>
            <div class="ja-field-row">
                <div class="ja-field">
                    <label>Kontakt na miejscu — imię <small style="color:#9ca3af; font-weight:400;">— do kogo się zgłosić</small></label>
                    <input type="text" name="order_contact_name" placeholder="np. p. Anna (recepcja)">
                </div>
                <div class="ja-field">
                    <label>Kontakt — telefon</label>
                    <input type="text" name="order_contact_phone" placeholder="np. 600 100 200">
                </div>
            </div>

            <!-- MATERIAŁY — proste pola (Nazwa + Link), wspólne dla zlecenia. Tłumacz dostaje je w mailu. -->
            <div class="ja-field">
                <label>Materiały do zlecenia <small style="color:#9ca3af; font-weight:400;">— linki dla tłumacza (opcjonalnie)</small></label>
                <div id="pjm-materials-rows">
                    <div class="pjm-mat-row">
                        <input type="text" name="order_mat_name[]" placeholder="Nazwa (np. Prezentacja)">
                        <input type="url" name="order_mat_url[]" placeholder="Link (https://...)">
                        <button type="button" class="pjm-mat-del" title="Usuń" aria-label="Usuń materiał">&times;</button>
                    </div>
                </div>
                <button type="button" id="pjm-mat-add" class="ja-btn" style="margin-top:6px;"><span class="material-symbols-rounded">add</span> Dodaj materiał</button>
            </div>

            <div class="ja-field">
                <label>Notatki wewnętrzne</label>
                <textarea name="admin_notes" rows="2" placeholder="Widoczne tylko dla admina..."></textarea>
            </div>
        </div>

        <!-- WYCENA -->
        <div class="ja-form-section">
            <h3><span class="material-symbols-rounded">payments</span> Wycena i płatność</h3>
            <div class="pjm-quote-box">
                <button type="button" class="ja-btn" id="pjm-mo-quote"><span class="material-symbols-rounded">calculate</span> Przelicz cenę</button>
                <div class="pjm-quote-result">Wycena serwerowa: <strong id="pjm-mo-quote-val">—</strong></div>
            </div>
            <div class="ja-field-row ja-field-row-3">
                <div class="ja-field">
                    <label>Kwota brutto (PLN) <span class="req">*</span></label>
                    <input type="number" name="total_price" id="pjm-mo-price" step="0.01" min="0" placeholder="0.00" required>
                    
                </div>
                <div class="ja-field">
                    <label>Metoda płatności</label>
                    <select name="payment_method"><?php foreach ( $payment_methods as $k => $v ) : ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
                </div>
                <div class="ja-field">
                    <label>Status zamówienia</label>
                    <select name="order_status"><?php foreach ( $status_options as $k => $v ) : ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
                </div>
            </div>
            <div class="ja-field-row">
                <div class="ja-field">
                    <label>Status płatności</label>
                    <select name="payment_status"><?php foreach ( $payment_status_options as $k => $v ) : ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
                </div>
                <div class="ja-field" style="align-self:end;">
                    <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="is_paid" value="1" style="width:auto;"> Oznacz jako już opłacone</label>
                </div>
            </div>
            
        </div>

        <!-- TŁUMACZE -->
        <div class="ja-form-section">
            <h3><span class="material-symbols-rounded">interpreter_mode</span> Tłumacze (opcjonalnie)</h3>
            <?php if ( empty( $translators ) ) : ?>
                <p class="ja-small" style="color:#888;">Brak tłumaczy w kartotece. Dodaj ich w zakładce „Tłumacze", aby przypisywać do zleceń.</p>
            <?php else : ?>
                <p class="ja-small" style="color:#888; margin-bottom:10px;">Przypisz osoby realizujące zlecenie. Możesz też zrobić to później w szczegółach zamówienia.</p>
                <div id="pjm-mo-translators"></div>
                <button type="button" class="ja-btn" id="pjm-mo-add-translator"><span class="material-symbols-rounded">person_add</span> Dodaj tłumacza</button>

                <template id="pjm-mo-tr-template">
                    <div class="pjm-mo-tr-row">
                        <select name="tr_id[]">
                            <option value="">— tłumacz —</option>
                            <?php foreach ( $translators as $t ) : ?><option value="<?php echo $t->id; ?>" data-rate="<?php echo esc_attr($t->default_rate); ?>"><?php echo esc_html($t->display_name); ?></option><?php endforeach; ?>
                        </select>
                        <select name="tr_from[]"><option value="">z…</option><?php foreach ( $lang_codes as $c => $i ) : ?><option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option><?php endforeach; ?></select>
                        <select name="tr_to[]"><option value="">na…</option><?php foreach ( $lang_codes as $c => $i ) : ?><option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option><?php endforeach; ?></select>
                        <select name="tr_role[]"><?php foreach ( $role_labels as $k => $v ) : ?><option value="<?php echo $k; ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
                        <input type="number" name="tr_cost[]" step="0.01" min="0" placeholder="koszt zł">
                        <button type="button" class="pjm-mo-tr-del" title="Usuń">&times;</button>
                    </div>
                </template>
            <?php endif; ?>
        </div>

        <div class="ja-form-actions">
            <button type="submit" class="ja-btn ja-btn-primary" id="pjm-mo-submit"><span class="material-symbols-rounded">add_circle</span> Utwórz zamówienie</button>
            <button type="reset" class="ja-btn">Wyczyść</button>
        </div>
    </form>
</div>

<style>
.ja-new-order-form { max-width: 920px; }
.ja-form-header { display:flex; align-items:center; gap:14px; margin-bottom:28px; padding-bottom:18px; border-bottom:1px solid var(--ja-border); }
.ja-form-section { background:var(--ja-card-bg,#fff); border:1px solid var(--ja-border); border-radius:10px; padding:24px; margin-bottom:20px; }
.ja-form-section h3 { display:flex; align-items:center; gap:8px; font-size:15px; margin:0 0 18px; color:var(--pjm-ink,#16241D); }
.ja-form-section h3 .material-symbols-rounded { font-size:20px; color:var(--pjm-primary,#1B5E4B); }
.ja-field { margin-bottom:14px; }
.ja-field label { display:block; font-size:13px; font-weight:600; color:var(--ja-text,#16241D); margin-bottom:5px; }
.ja-field .req { color:#e53935; }
.ja-field input, .ja-field select, .ja-field textarea { width:100%; padding:10px 12px; border:1px solid var(--ja-border,#dfe6e9); border-radius:8px; font-size:14px; font-family:inherit; background:#fff; box-sizing:border-box; }
.ja-field input:focus, .ja-field select:focus, .ja-field textarea:focus { border-color:var(--pjm-primary,#1B5E4B); outline:none; box-shadow:0 0 0 3px rgba(27,94,75,0.12); }
.ja-field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.ja-field-row-3 { grid-template-columns:1fr 1fr 1fr; }
@media (max-width:700px){ .ja-field-row, .ja-field-row-3 { grid-template-columns:1fr; } }
.ja-form-actions { display:flex; gap:12px; }
.ja-btn-primary { background:var(--pjm-primary,#1B5E4B); color:#fff; border-color:var(--pjm-primary,#1B5E4B); }
.ja-btn-primary:hover { background:var(--pjm-primary-dark,#154A3B); }
.ja-btn-primary .material-symbols-rounded { font-size:18px; margin-right:4px; }
.ja-field label input[type=checkbox] { width:auto; margin-right:6px; vertical-align:middle; }
.pjm-quote-box { display:flex; align-items:center; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
.pjm-quote-result { font-size:14px; color:#555; } .pjm-quote-result strong { color:var(--pjm-primary,#1B5E4B); font-size:18px; }
.pjm-mo-tr-row { display:grid; grid-template-columns: 1.6fr 0.9fr 0.9fr 1.3fr 0.9fr auto; gap:8px; margin-bottom:8px; align-items:center; }
.pjm-mo-tr-row select, .pjm-mo-tr-row input { padding:8px; border:1px solid var(--ja-border,#dfe6e9); border-radius:6px; font-size:13px; }
.pjm-mo-tr-del { background:#fee2e2; color:#b91c1c; border:none; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:18px; }
@media (max-width:700px){ .pjm-mo-tr-row { grid-template-columns:1fr 1fr; } }
/* Przełącznik miejsca (stacjonarnie/online) */
.pjm-mode-toggle { display:inline-flex; background:#f1f3f5; border-radius:999px; padding:4px; gap:2px; }
.pjm-mode-btn { border:none; background:transparent; color:#5f6f67; font-size:13px; font-weight:600; padding:8px 16px; border-radius:999px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.pjm-mode-btn .material-symbols-rounded { font-size:18px; }
.pjm-mode-btn.is-active { background:#14352A; color:#fff; }
/* Wiersze materiałów */
.pjm-mat-row { display:grid; grid-template-columns: 1fr 1.4fr auto; gap:8px; margin-bottom:8px; align-items:center; }
.pjm-mat-row input { padding:9px 11px; border:1px solid var(--ja-border,#dfe6e9); border-radius:8px; font-size:14px; box-sizing:border-box; }
.pjm-mat-del { background:#fee2e2; color:#b91c1c; border:none; width:38px; height:38px; border-radius:8px; cursor:pointer; font-size:18px; }
@media (max-width:600px){ .pjm-mat-row { grid-template-columns:1fr auto; } .pjm-mat-row input[name='order_mat_url[]'] { grid-column:1 / -1; } }
</style>

<script>
jQuery(function($){
    var ajax = pjm_admin_vars.ajax_url;

    function showSvc(){
        var s = $('#pjm-mo-service').val();
        $('.pjm-svc-fields').hide();
        $('.pjm-svc-fields[data-svc="'+s+'"]').show();
        // Termin realizacji (do kiedy) tylko dla tłumaczenia tekstu/wideo.
        $('.pjm-deadline-row').toggle(s === 'video' || s === 'text');
    }
    $('#pjm-mo-service').on('change', showSvc); showSvc();

    // Przełącznik miejsca: stacjonarnie/online → pokaż właściwe pole + ustaw tryb.
    $(document).on('click', '.pjm-mode-btn', function(){
        var mode = $(this).data('mode');
        $('.pjm-mode-btn').removeClass('is-active');
        $(this).addClass('is-active');
        $('#pjm-order-mode').val(mode);
        $('.pjm-mode-field').hide().filter('[data-mode="'+mode+'"]').show();
        // czyść nieaktywne pole, żeby nie zapisać obu
        if (mode === 'stacjonarnie') $('input[name=order_meeting_url]').val('');
        else $('input[name=order_location]').val('');
    });

    // Materiały: dodawanie/usuwanie wierszy (Nazwa + Link).
    $('#pjm-mat-add').on('click', function(){
        $('#pjm-materials-rows').append(
            '<div class="pjm-mat-row">'+
            '<input type="text" name="order_mat_name[]" placeholder="Nazwa (np. Prezentacja)">'+
            '<input type="url" name="order_mat_url[]" placeholder="Link (https://...)">'+
            '<button type="button" class="pjm-mat-del" title="Usuń" aria-label="Usuń materiał">&times;</button>'+
            '</div>'
        );
    });
    $(document).on('click', '.pjm-mat-del', function(){
        if ($('#pjm-materials-rows .pjm-mat-row').length > 1) $(this).closest('.pjm-mat-row').remove();
        else $(this).closest('.pjm-mat-row').find('input').val('');
    });

    // Status płatności podpowiadany na podstawie metody (admin może nadpisać ręcznie).
    $('select[name=payment_method]').on('change', function(){
        if ($('input[name=is_paid]').is(':checked')) return;
        var map = { 'faktura_po':'deferred', 'proforma':'proforma', 'online':'unpaid', 'transfer':'unpaid', 'cash':'paid' };
        var v = map[$(this).val()];
        if (v) $('select[name=payment_status]').val(v);
    });
    $('input[name=is_paid]').on('change', function(){
        if ($(this).is(':checked')) $('select[name=payment_status]').val('paid');
    });

    $('#pjm-mo-client').on('change', function(){ $('#pjm-mo-new-client').toggle($(this).val()==='new'); });
    $('#pjm-mo-new-ctype').on('change', function(){ $('#pjm-mo-company-fields').toggle($(this).val()!=='private'); });

    // Budowa pozycji w formacie kalkulatora (do pjm_quote)
    function buildItem(){
        var s = $('#pjm-mo-service').val();
        var item = { service_type:s, meta:{}, quantity:1, _addons_data:[], delivery:{mode:'standard'} };
        if (s==='video'){
            item.quantity = parseFloat($('[name=video_films]').val())||1;
            item.meta.duration_val = parseInt($('[name=video_minutes]').val())||0;
            item.delivery.mode = $('[name=video_delivery]').val()||'standard';
        } else if (s==='text'){
            item.meta.chars_count = parseInt($('[name=text_chars]').val())||0;
            item.delivery.mode = $('[name=text_delivery]').val()||'standard';
        } else if (s==='event'){
            // Wiele dni — każdy „YYYY-MM-DD (HH:MM-HH:MM)"; serwer zsumuje godziny ze wszystkich dni.
            var parts=[];
            $('#pjm-event-days .pjm-eday').each(function(){
                var d=$(this).find('[name="event_day[]"]').val();
                var f=$(this).find('[name="event_from[]"]').val();
                var t=$(this).find('[name="event_to[]"]').val();
                if(d){ parts.push(d + ((f&&t)?(' ('+f+'-'+t+')'):'')); }
            });
            item.meta.dates = parts.join(', ');
            var f0=$('#pjm-event-days .pjm-eday:first [name="event_from[]"]').val(), t0=$('#pjm-event-days .pjm-eday:first [name="event_to[]"]').val();
            item.meta.schedule = (f0&&t0) ? ('('+f0+' - '+t0+')') : '';
            item.meta.location = $('[name=event_location]').val()||'';
        } else if (s==='loop'){
            var d1=$('[name=loop_from]').val(), d2=$('[name=loop_to]').val();
            item.meta.dates = (d1&&d2) ? (d1+' – '+d2) : '';
        }
        return item;
    }

    function quote(){
        var s = $('#pjm-mo-service').val();
        if (s==='other' || s==='subscription'){ $('#pjm-mo-quote-val').text('wpisz ręcznie'); return; }
        $('#pjm-mo-quote-val').text('liczę…');
        $.post(ajax, { action:'pjm_quote', nonce:pjm_admin_vars.calc_nonce, item: JSON.stringify(buildItem()) })
        .done(function(res){
            if (res.success){
                var v = parseFloat(res.data.total)||0;
                $('#pjm-mo-quote-val').text(v.toFixed(2).replace('.',',')+' zł');
                $('#pjm-mo-price').val(v.toFixed(2));
            } else { $('#pjm-mo-quote-val').text('błąd: '+(res.data||'')); }
        }).fail(function(){ $('#pjm-mo-quote-val').text('błąd połączenia'); });
    }
    $('#pjm-mo-quote').on('click', quote);
    $(document).on('change', '[data-quote]', quote);

    // Wydarzenie: dodawanie/usuwanie dni (każdy z własnymi godzinami)
    $('#pjm-event-add-day').on('click', function(){
        var $row = $('#pjm-event-days .pjm-eday').first().clone();
        $row.find('input[type=date]').val('');
        $('#pjm-event-days').append($row);
    });
    $('#pjm-event-days').on('click', '.pjm-eday-del', function(){
        if ( $('#pjm-event-days .pjm-eday').length > 1 ) { $(this).closest('.pjm-eday').remove(); quote(); }
    });

    // Tłumacze — dodawanie/usuwanie wierszy
    $('#pjm-mo-add-translator').on('click', function(){
        var tpl = document.getElementById('pjm-mo-tr-template');
        if (tpl) $('#pjm-mo-translators').append(tpl.innerHTML);
    });
    $(document).on('click', '.pjm-mo-tr-del', function(){ $(this).closest('.pjm-mo-tr-row').remove(); });
    $(document).on('change', '.pjm-mo-tr-row select[name="tr_id[]"]', function(){
        var rate = $(this).find('option:selected').data('rate');
        var $cost = $(this).closest('.pjm-mo-tr-row').find('input[name="tr_cost[]"]');
        if (rate && !$cost.val()) $cost.val(rate);
    });

    $('#pjm-manual-order-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#pjm-mo-submit');
        $btn.prop('disabled', true).find('.material-symbols-rounded').text('sync');
        var data = $(this).serializeArray();
        data.push({name:'action', value:'pjm_admin_create_order'});
        data.push({name:'nonce', value:pjm_admin_vars.nonce});
        $.post(ajax, $.param(data))
        .done(function(res){
            if (res.success){ alert('Zamówienie #'+res.data.order_id+' utworzone!'); location.reload(); }
            else alert('Błąd: '+(res.data||''));
        }).fail(function(){ alert('Błąd połączenia.'); })
        .always(function(){ $btn.prop('disabled', false).find('.material-symbols-rounded').text('add_circle'); });
    });
});
</script>
