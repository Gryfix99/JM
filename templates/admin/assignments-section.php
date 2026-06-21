<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Sekcja "Tłumacze / Przypisania" w szczegółach zamówienia.
 * Model: koszt tłumacza ponosi FIRMA (nie klient) — pokazujemy tylko koszt.
 * Oczekuje w zasięgu: $order_id, $assignments, $translators, $lang_codes
 */
if ( ! isset( $assignments ) ) $assignments = pjm_get_assignments_by_order( $order_id );
if ( ! isset( $translators ) ) $translators = pjm_get_translators( 'active' );
if ( ! isset( $lang_codes ) )  $lang_codes  = pjm_get_language_codes();

// Tylko dwie role.
$role_labels = [
    'translator' => 'Tłumacz',
    'technician' => 'Obsługa techniczna',
];
$status_labels = [
    'assigned'    => 'Przypisany', 'sent' => 'Wysłano brief', 'accepted' => 'Zaakceptował', 'in_progress' => 'W realizacji',
    'delivered'   => 'Dostarczone', 'settled' => 'Rozliczone', 'declined' => 'Odrzucone', 'cancelled' => 'Anulowane',
];

// Wykryj zlecenie pętli indukcyjnej → wtedy domyślna rola to „obsługa techniczna" (nie tłumacz), ta sama lista osób.
global $wpdb;
$order_is_loop = false;
$ord = $wpdb->get_row( $wpdb->prepare( "SELECT package_id, order_json FROM {$wpdb->prefix}pjm_orders WHERE id = %d", $order_id ) );
if ( $ord ) {
    if ( $ord->package_id === 'loop' ) {
        $order_is_loop = true;
    } else {
        $first = json_decode( $ord->order_json, true );
        if ( is_array( $first ) && isset( $first[0]['service_type'] ) && $first[0]['service_type'] === 'loop' ) $order_is_loop = true;
    }
}
$default_role = $order_is_loop ? 'technician' : 'translator';

// Prefill daty/godzin/miejsca/języków z zamówienia — żeby admin nie przepisywał tego, co już wpisał klient.
$pf_date = $pf_start = $pf_end = $pf_location = $pf_lang_from = $pf_lang_to = '';
if ( $ord ) {
    $pf_items = json_decode( $ord->order_json, true );
    if ( is_array( $pf_items ) ) {
        foreach ( $pf_items as $it ) {
            $meta = is_array( $it['meta'] ?? null ) ? $it['meta'] : [];
            // Języki zamówienia (jeśli zapisane na poziomie zlecenia) — prefill pierwszego napotkanego.
            if ( $pf_lang_from === '' && ! empty( $meta['order_lang_from'] ) ) $pf_lang_from = (string) $meta['order_lang_from'];
            if ( $pf_lang_to === '' && ! empty( $meta['order_lang_to'] ) )     $pf_lang_to   = (string) $meta['order_lang_to'];
            // Miejsce / link spotkania.
            if ( $pf_location === '' && ! empty( $meta['location'] ) )     $pf_location = (string) $meta['location'];
            if ( $pf_location === '' && ! empty( $meta['meeting_url'] ) )  $pf_location = (string) $meta['meeting_url'];
            $stype = $it['service_type'] ?? '';
            if ( $stype === 'event' || $stype === 'loop' ) {
                $dates = (string) ( $meta['dates'] ?? '' );
                $sched = (string) ( $meta['schedule'] ?? '' );
                if ( preg_match( '/(\d{4}-\d{2}-\d{2})/', $dates, $dm ) ) $pf_date = $dm[1];
                if ( preg_match( '/(\d{2}:\d{2})\s*[-–]\s*(\d{2}:\d{2})/', $dates . ' ' . $sched, $hm ) ) { $pf_start = $hm[1]; $pf_end = $hm[2]; }
            }
        }
    }
}

$sum_cost = 0;
foreach ( $assignments as $a ) { $sum_cost += (float) $a->cost_total; }
?>

<div class="pjm-asg">
    <div class="pjm-asg-list">
        <?php if ( empty( $assignments ) ) : ?>
            <p class="pjm-asg-empty">Brak przypisanych tłumaczy. Dodaj poniżej, kto realizuje to zlecenie.</p>
        <?php else : ?>
            <div class="pjm-asg-cards">
                <?php foreach ( $assignments as $a ) :
                    $locked  = ! empty( $a->settlement_period );
                    $row_pair = pjm_lang_pair( $a->lang_from, $a->lang_to, $a->role );
                    $tax_v   = ( $a->rate_tax === 'brutto' ) ? 'brutto' : 'netto';
                    $h_disp  = rtrim( rtrim( number_format( (float)$a->hours, 2, ',', '' ), '0' ), ',' );
                    $start_v = $a->start_time ? substr( $a->start_time, 0, 5 ) : '';
                    $end_v   = $a->end_time   ? substr( $a->end_time, 0, 5 )   : '';
                    $date_v  = $a->work_date  ?: '';
                    $qty_v   = rtrim( rtrim( number_format( (float)$a->quantity, 2, '.', '' ), '0' ), '.' );
                    // Inicjały do awatara.
                    $nm = $a->translator_name ?: ( '#' . $a->translator_id );
                    $np = preg_split( '/\s+/', trim( $nm ) );
                    $ini = mb_strtoupper( mb_substr( $np[0] ?? 'T', 0, 1 ) . ( count( $np ) > 1 ? mb_substr( end( $np ), 0, 1 ) : '' ) );
                    $mats = json_decode( $a->materials_json, true );
                ?>
                    <div class="pjm-asg-card" data-aid="<?php echo $a->id; ?>">
                        <div class="pjm-asg-c-top">
                            <div class="pjm-asg-person">
                                <span class="pjm-asg-ava"><?php echo esc_html( $ini ); ?></span>
                                <span class="pjm-asg-name"><?php echo esc_html( $nm ); ?></span>
                            </div>
                            <div class="pjm-asg-tags">
                                <?php if ( $row_pair !== '' ) : ?><span class="pjm-asg-pair"><?php echo esc_html( $row_pair ); ?></span><?php endif; ?>
                                <span class="pjm-asg-role"><?php echo esc_html( $role_labels[ $a->role ] ?? $a->role ); ?></span>
                            </div>
                        </div>

                        <div class="pjm-asg-c-when">
                            <?php if ( $locked ) : ?>
                                <span class="pjm-asg-small"><?php echo $date_v ? esc_html( date_i18n( 'd.m.Y', strtotime( $date_v ) ) ) : ''; ?> <?php echo ( $start_v && $end_v ) ? esc_html( $start_v . '–' . $end_v ) : ''; ?> <?php echo (float)$a->hours > 0 ? '· ' . $h_disp . ' godz.' : ''; ?></span>
                            <?php else : ?>
                                <div class="pjm-asg-hours">
                                    <input type="date" class="asg-date" value="<?php echo esc_attr( $date_v ); ?>" title="Data pracy">
                                    <input type="time" class="asg-start" value="<?php echo esc_attr( $start_v ); ?>" title="Godz. rozp.">
                                    <span>–</span>
                                    <input type="time" class="asg-end" value="<?php echo esc_attr( $end_v ); ?>" title="Godz. zak.">
                                    <span class="asg-hrs-wrap"><span class="asg-hrs"><?php echo (float)$a->hours > 0 ? $h_disp : '—'; ?></span> godz.</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pjm-asg-c-money">
                            <?php if ( $locked ) : ?>
                                <span class="pjm-asg-small"><?php echo number_format( (float)$a->cost_unit_rate, 2, ',', ' ' ); ?> zł/godz <?php echo esc_html( $tax_v ); ?></span>
                            <?php else : ?>
                                <div class="pjm-asg-costedit">
                                    <input type="hidden" class="asg-qty" value="<?php echo esc_attr( $qty_v ); ?>">
                                    <input type="number" class="asg-rate" step="0.01" min="0" value="<?php echo esc_attr( number_format( (float)$a->cost_unit_rate, 2, '.', '' ) ); ?>" title="Stawka zł/godz"> zł/godz
                                    <select class="asg-ratetax" title="netto/brutto"><option value="netto" <?php selected( $tax_v, 'netto' ); ?>>netto</option><option value="brutto" <?php selected( $tax_v, 'brutto' ); ?>>brutto</option></select>
                                </div>
                            <?php endif; ?>
                            <div class="pjm-asg-sum">Σ <strong class="asg-total"><?php echo number_format( (float)$a->cost_total, 2, ',', ' ' ); ?></strong> zł</div>
                        </div>

                        <?php if ( ! empty( $a->location ) || ! empty( $a->meeting_url ) || ! empty( $a->contact_name ) || ! empty( $a->contact_phone ) || ( is_array( $mats ) && $mats ) ) : ?>
                        <div class="pjm-asg-c-info">
                            <?php if ( ! empty( $a->location ) ) : ?><div>Miejsce: <?php echo esc_html( $a->location ); ?></div><?php endif; ?>
                            <?php if ( ! empty( $a->meeting_url ) ) : ?><div><a href="<?php echo esc_url( $a->meeting_url ); ?>" target="_blank" rel="noopener">link spotkania</a></div><?php endif; ?>
                            <?php if ( ! empty( $a->contact_name ) || ! empty( $a->contact_phone ) ) : ?><div>Kontakt: <?php echo esc_html( trim( $a->contact_name . ( $a->contact_phone ? ' · ' . $a->contact_phone : '' ) ) ); ?></div><?php endif; ?>
                            <?php if ( is_array( $mats ) && $mats ) : ?><div>Materiały: <?php foreach ( $mats as $m ) : if ( empty( $m['url'] ) ) continue; ?><a href="<?php echo esc_url( $m['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( ( $m['name'] ?? '' ) ?: 'Materiał' ); ?></a> <?php endforeach; ?></div><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="pjm-asg-c-foot">
                            <span class="pjm-asg-badge st-<?php echo esc_attr( $a->assignment_status ); ?>"><?php echo esc_html( $status_labels[ $a->assignment_status ] ?? $a->assignment_status ); ?></span>
                            <div class="pjm-asg-actions">
                                <?php if ( ! $locked ) : ?>
                                    <button class="pjm-asg-ic pjm-asg-save" data-aid="<?php echo $a->id; ?>" title="Zapisz koszt"><span class="material-symbols-rounded">save</span></button>
                                    <button class="pjm-asg-ic pjm-asg-send" data-aid="<?php echo $a->id; ?>" title="Wyślij brief do tłumacza"><span class="material-symbols-rounded">send</span></button>
                                    <button class="pjm-asg-ic pjm-asg-del" data-aid="<?php echo $a->id; ?>" title="Usuń"><span class="material-symbols-rounded">delete</span></button>
                                <?php else : ?>
                                    <span class="pjm-asg-lock" title="Zamknięty miesiąc <?php echo esc_attr( $a->settlement_period ); ?>"><span class="material-symbols-rounded" style="font-size:16px;">lock</span></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="pjm-asg-sumbar">Suma kosztów tłumaczy (firmy): <strong><?php echo number_format( $sum_cost, 2, ',', ' ' ); ?> zł</strong></div>
            </div>
        <?php endif; ?>
    </div>

    <details class="pjm-asg-addbox"<?php echo $order_is_loop ? ' open' : ''; ?>>
        <summary>+ Przypisz osobę (tłumacz / obsługa techniczna)</summary>
        <form class="pjm-asg-form" data-order="<?php echo (int) $order_id; ?>">
            <div class="pjm-asg-grid">
                <div class="pjm-asg-fld" style="grid-column:1/-1;">
                    <label>Rola</label>
                    <select name="role" class="pjm-asg-role">
                        <?php foreach ( $role_labels as $k => $v ) : ?><option value="<?php echo $k; ?>" <?php selected( $default_role, $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="pjm-asg-fld pjm-asg-langfield">
                    <label>Język A</label>
                    <select name="lang_from" class="pjm-asg-langsel"><option value="">—</option>
                        <?php foreach ( $lang_codes as $code => $info ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $pf_lang_from, $code ); ?>><?php echo esc_html( $code . ' — ' . ( $info['label'] ?? '' ) ); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="pjm-asg-fld pjm-asg-langfield">
                    <label>Język B</label>
                    <select name="lang_to" class="pjm-asg-langsel"><option value="">—</option>
                        <?php foreach ( $lang_codes as $code => $info ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $pf_lang_to, $code ); ?>><?php echo esc_html( $code . ' — ' . ( $info['label'] ?? '' ) ); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="pjm-asg-fld" style="grid-column:1/-1;">
                    <label>Osoba <span class="pjm-asg-filterhint" style="font-weight:400; color:#9ca3af;"></span></label>
                    <select name="translator_id" required>
                        <option value="">— wybierz —</option>
                        <?php foreach ( $translators as $t ) :
                            $t_langs = json_decode( $t->lang_codes_json ?? '', true ); if ( ! is_array( $t_langs ) ) $t_langs = [];
                        ?>
                            <option value="<?php echo $t->id; ?>" data-rate="<?php echo esc_attr( $t->default_rate ); ?>" data-langs="<?php echo esc_attr( wp_json_encode( $t_langs ) ); ?>"><?php echo esc_html( $t->display_name ); echo $t->default_rate ? ' · ' . number_format( (float) $t->default_rate, 2, ',', ' ' ) . ' zł/h' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pjm-asg-fld">
                    <label>Data pracy</label>
                    <input type="date" name="work_date" value="<?php echo esc_attr( $pf_date ); ?>">
                </div>
                <div class="pjm-asg-fld">
                    <label>Godz. rozp.</label>
                    <input type="time" name="start_time" value="<?php echo esc_attr( $pf_start ); ?>">
                </div>
                <div class="pjm-asg-fld">
                    <label>Godz. zak.</label>
                    <input type="time" name="end_time" value="<?php echo esc_attr( $pf_end ); ?>">
                </div>
                <div class="pjm-asg-fld">
                    <label>Stawka (zł/godz)</label>
                    <input type="number" name="cost_unit_rate" step="0.01" min="0" placeholder="domyślna">
                </div>
                <div class="pjm-asg-fld">
                    <label>Stawka netto / brutto</label>
                    <select name="rate_tax">
                        <option value="netto">netto</option>
                        <option value="brutto">brutto</option>
                    </select>
                </div>
                <input type="hidden" name="quantity" value="1">
                <?php if ( $pf_location !== '' ) : ?>
                <div class="pjm-asg-fld" style="grid-column:1/-1;">
                    <div class="pjm-asg-place-note">Miejsce/link tego zlecenia: <strong><?php echo esc_html( $pf_location ); ?></strong> </div>
                </div>
                <?php endif; ?>
                <div class="pjm-asg-fld" style="grid-column:1/-1;">
                    <label>Opis pozycji (na rozliczenie)</label>
                    <input type="text" name="description" placeholder="np. Tłumaczenie spotkania zarządu">
                </div>
                <div class="pjm-asg-fld" style="grid-column:1/-1;">
                    
                </div>
            </div>
            <div class="pjm-asg-foot">
                <span class="pjm-asg-live">Koszt: <strong class="pjm-asg-livecost">0,00</strong> zł<span class="pjm-asg-livehrs" style="color:#6b7280;"></span></span>
                <button type="submit" class="pjm-btn pjm-btn-primary" style="width:auto;">Przypisz</button>
            </div>
        </form>
    </details>
</div>

<style>
.pjm-asg-note { display:flex; align-items:center; gap:6px; font-size:12px; color:#5f6f67; background:#eef5f1; border:1px solid #d6e7df; border-radius:8px; padding:8px 12px; margin:0 0 12px; }
.pjm-asg-note .material-symbols-rounded { font-size:16px; color:#1B5E4B; }
/* === KARTY TŁUMACZY: [Osoba | Język] / [Godziny] / [Cena | Suma] === */
.pjm-asg-cards { display:flex; flex-direction:column; gap:10px; }
.pjm-asg-card { border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; background:#fff; }
.pjm-asg-c-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.pjm-asg-person { display:flex; align-items:center; gap:9px; min-width:0; }
.pjm-asg-ava { width:34px; height:34px; border-radius:50%; background:#E1F0EA; color:#14352A; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex:0 0 auto; }
.pjm-asg-name { font-weight:600; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pjm-asg-tags { display:flex; align-items:center; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
.pjm-asg-role { font-size:11px; color:#5f6f67; background:#f1f3f5; padding:2px 8px; border-radius:6px; white-space:nowrap; }
.pjm-asg-c-when { margin-top:10px; padding-top:10px; border-top:1px solid #f0efe9; }
.pjm-asg-c-money { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; flex-wrap:wrap; }
.pjm-asg-sum { font-size:16px; font-weight:700; color:#14352A; white-space:nowrap; }
.pjm-asg-sum .asg-total { color:#14352A; }
.pjm-asg-c-info { margin-top:10px; padding-top:10px; border-top:1px solid #f0efe9; font-size:12px; color:#42514a; line-height:1.7; }
.pjm-asg-c-info a { color:#1B5E4B; }
.pjm-asg-c-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:10px; }
.pjm-asg-actions { display:flex; gap:6px; }
.pjm-asg-hrs-wrap { font-size:12px; color:#9ca3af; }
.pjm-asg-sumbar { text-align:right; font-size:14px; color:#42514a; padding:8px 2px 0; }
.pjm-asg-sumbar strong { color:#14352A; }
.pjm-asg-table { width:100%; border-collapse:collapse; font-size:13px; }
.pjm-asg-table th { text-align:left; font-size:11px; color:#6b7280; padding:8px 6px; border-bottom:1px solid #e5e7eb; text-transform:uppercase; }
.pjm-asg-table td { padding:10px 6px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.pjm-asg-desc { font-size:11px; color:#9ca3af; margin-top:2px; }
.pjm-asg-sent { font-size:10px; color:#16a34a; }
.pjm-asg-small { font-size:11px; color:#9ca3af; }
.pjm-asg-pair { background:#eef5f1; color:#1B5E4B; padding:2px 7px; border-radius:6px; font-weight:600; font-size:12px; white-space:nowrap; }
.pjm-asg-costedit { display:flex; align-items:center; gap:4px; font-size:12px; }
.pjm-asg-costedit input { width:58px; padding:5px 6px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; }
.pjm-asg-costedit select.asg-ratetax { padding:4px 4px; border:1px solid #d1d5db; border-radius:6px; font-size:11px; }
.pjm-asg-costedit .asg-total { color:#1B5E4B; }
.pjm-asg-hours { display:flex; align-items:center; gap:4px; margin-bottom:5px; }
.pjm-asg-hours input { padding:4px 5px; border:1px solid #d1d5db; border-radius:6px; font-size:11px; box-sizing:border-box; }
.pjm-asg-hours input.asg-date { width:120px; } .pjm-asg-hours input.asg-start, .pjm-asg-hours input.asg-end { width:72px; }
.pjm-asg-fld textarea { width:100%; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; box-sizing:border-box; font-family:inherit; }
.pjm-asg-badge { padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; }
.pjm-asg-badge.st-assigned{background:#f3f4f6;color:#4b5563;} .pjm-asg-badge.st-accepted{background:#e0f2fe;color:#0369a1;}
.pjm-asg-badge.st-in_progress{background:#fef3c7;color:#b45309;} .pjm-asg-badge.st-delivered{background:#dcfce7;color:#15803d;}
.pjm-asg-badge.st-settled{background:#ddd6fe;color:#6d28d9;} .pjm-asg-badge.st-cancelled{background:#fee2e2;color:#b91c1c;}
.pjm-asg-empty { color:#9ca3af; font-size:13px; padding:8px 0; }
.pjm-asg-mat { margin-top:6px; }
.pjm-asg-mat summary { cursor:pointer; font-size:11px; color:#1B5E4B; font-weight:600; display:inline-flex; align-items:center; gap:3px; list-style:none; }
.pjm-asg-mat summary::-webkit-details-marker { display:none; }
.pjm-asg-mat summary .material-symbols-rounded { font-size:14px; }
.pjm-asg-mat textarea { width:100%; max-width:260px; margin-top:5px; padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; box-sizing:border-box; font-family:inherit; }
.pjm-asg-matsave { margin-top:5px; border:1px solid #1B5E4B; background:#1B5E4B; color:#fff; font-size:11px; font-weight:600; padding:4px 10px; border-radius:6px; cursor:pointer; }
.pjm-asg-matsave:hover { background:#15493a; }
.pjm-asg-mat input { display:block; width:100%; max-width:260px; margin-top:5px; padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; box-sizing:border-box; }
.pjm-asg-place-ro { margin-top:6px; font-size:12px; color:#42514a; line-height:1.6; }
.pjm-asg-place-ro a { color:#1B5E4B; }
.pjm-asg-place-note { font-size:12px; color:#5f6f67; background:#eef5f1; border:1px solid #d6e7df; border-radius:8px; padding:8px 12px; }
.pjm-asg-badge.st-sent{background:#e0e7ff;color:#3730a3;} .pjm-asg-badge.st-declined{background:#fee2e2;color:#b91c1c;}
.pjm-asg-ic { border:none; background:#f1f3f5; width:30px; height:30px; border-radius:6px; cursor:pointer; color:#555; }
.pjm-asg-ic:hover { background:#e9ecef; color:#1B5E4B; } .pjm-asg-ic .material-symbols-rounded{font-size:17px;}
.pjm-asg-addbox { margin-top:14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; }
.pjm-asg-addbox summary { cursor:pointer; font-weight:600; font-size:13px; color:#1B5E4B; }
.pjm-asg-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:12px; }
@media(max-width:640px){ .pjm-asg-grid{ grid-template-columns:1fr 1fr; } }
.pjm-asg-fld label { display:block; font-size:11px; color:#6b7280; margin-bottom:3px; font-weight:600; }
.pjm-asg-fld input, .pjm-asg-fld select { width:100%; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; box-sizing:border-box; }
.pjm-asg-foot { display:flex; justify-content:space-between; align-items:center; margin-top:12px; }
.pjm-asg-live { font-size:13px; color:#374151; }
.pjm-asg-lock { font-size:14px; }
/* Responsywność — na wąskich szerokościach (modal podglądu) zamiast przewijania w bok
   każdy wiersz układa się w pionie jako karta. Nagłówek znika, etykiety z data-label. */
.pjm-asg { overflow-x: hidden; }
@media(max-width:720px){
  .pjm-asg-table, .pjm-asg-table tbody, .pjm-asg-table tr, .pjm-asg-table td { display:block; width:100%; box-sizing:border-box; }
  .pjm-asg-table thead, .pjm-asg-table tfoot { display:none; }
  .pjm-asg-table tr { border:1px solid #e5e7eb; border-radius:10px; margin-bottom:12px; padding:10px 12px; background:#fff; }
  .pjm-asg-table td { border:none; padding:5px 0; }
  .pjm-asg-table td[data-l]::before { content:attr(data-l); display:block; font-size:10px; text-transform:uppercase; color:#9ca3af; font-weight:600; margin-bottom:2px; }
  .pjm-asg-hours { flex-wrap:wrap; }
  .pjm-asg-costedit { flex-wrap:wrap; }
}
</style>

<script>
jQuery(function($){
    function hoursBetween(start, end){
        if(!start || !end) return 0;
        var s=start.split(':'), e=end.split(':');
        if(s.length<2 || e.length<2) return 0;
        var diff=(parseInt(e[0],10)*60+parseInt(e[1],10))-(parseInt(s[0],10)*60+parseInt(s[1],10));
        if(diff<0) diff+=1440; // przez północ
        return Math.round((diff/60)*100)/100;
    }
    function recalc($form){
        var r = parseFloat($form.find('[name=cost_unit_rate]').val());
        if(isNaN(r)){ r = parseFloat($form.find('[name=translator_id] option:selected').data('rate'))||0; }
        var h = hoursBetween($form.find('[name=start_time]').val(), $form.find('[name=end_time]').val());
        var qty = parseFloat($form.find('[name=quantity]').val())||1;
        var total = h>0 ? h*r : qty*r;
        $form.find('.pjm-asg-livecost').text(total.toFixed(2).replace('.',','));
        $form.find('.pjm-asg-livehrs').text(h>0 ? (' · '+String(h).replace('.',',')+' godz') : '');
    }
    function reload($ctx){
        var oid = $ctx.closest('.pjm-asg').find('.pjm-asg-form').data('order');
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_get_assignments', order_id:oid, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success){ $('#pjm-asg-wrap').html(res.data.html); }
        });
    }

    $(document).off('input.pjmasg', '.pjm-asg-form').on('input.pjmasg', '.pjm-asg-form', function(){ recalc($(this)); });
    $(document).off('change.pjmasg', '.pjm-asg-form [name=translator_id]').on('change.pjmasg', '.pjm-asg-form [name=translator_id]', function(){ recalc($(this).closest('form')); });

    // Rola „obsługa techniczna" → para językowa nieistotna: chowamy pola języków i nie filtrujemy.
    // Tłumacz → ograniczamy listę osób do tych, które obsługują wybrane języki.
    function applyLangFilter($form){
        var isTech = $form.find('.pjm-asg-role').val() === 'technician';
        $form.find('.pjm-asg-langfield').toggle(!isTech);
        var from = $form.find('[name=lang_from]').val() || '';
        var to   = $form.find('[name=lang_to]').val() || '';
        var need = [];
        if(!isTech){ if(from) need.push(from); if(to) need.push(to); }
        var $sel = $form.find('[name=translator_id]'), shown=0, total=0;
        $sel.find('option').each(function(){
            var $o=$(this); if(!$o.val()) return;
            total++;
            var langs=[]; try{ langs=JSON.parse($o.attr('data-langs')||'[]'); }catch(e){}
            var ok = need.every(function(c){ return langs.indexOf(c) !== -1; });
            // Brak zadeklarowanych języków u tłumacza → nie ukrywamy (nie wiemy = pokaż).
            if(langs.length===0) ok = true;
            $o.prop('hidden', !ok).prop('disabled', !ok);
            if(ok) shown++;
        });
        if($sel.find('option:selected').prop('hidden')) $sel.val('');
        var hint = (!isTech && need.length) ? ('· pasuje '+shown+'/'+total) : '';
        $form.find('.pjm-asg-filterhint').text(hint);
    }
    $(document).off('change.pjmasgflt', '.pjm-asg-role, .pjm-asg-langsel').on('change.pjmasgflt', '.pjm-asg-role, .pjm-asg-langsel', function(){
        applyLangFilter($(this).closest('form'));
    });

    // Live przeliczanie w wierszu (godziny × stawka; fallback ilość × stawka)
    $(document).off('input.pjmasgrow', '.asg-date, .asg-start, .asg-end, .asg-rate').on('input.pjmasgrow', '.asg-date, .asg-start, .asg-end, .asg-rate', function(){
        var $row=$(this).closest('.pjm-asg-card');
        var h=hoursBetween($row.find('.asg-start').val(), $row.find('.asg-end').val());
        var r=parseFloat($row.find('.asg-rate').val())||0;
        var qty=parseFloat($row.find('.asg-qty').val())||0;
        var total=h>0 ? h*r : qty*r;
        $row.find('.asg-hrs').text(h>0 ? String(h).replace('.',',') : '—');
        $row.find('.asg-total').text(total.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,' '));
    });

    // Zapis zmienionego kosztu (data + godziny + stawka)
    $(document).off('click.pjmasgsave', '.pjm-asg-save').on('click.pjmasgsave', '.pjm-asg-save', function(){
        var aid=$(this).data('aid'), $row=$(this).closest('.pjm-asg-card');
        $.post(pjm_admin_vars.ajax_url, {
            action:'pjm_admin_update_assignment', assignment_id:aid,
            work_date:$row.find('.asg-date').val(),
            start_time:$row.find('.asg-start').val(),
            end_time:$row.find('.asg-end').val(),
            cost_unit_rate:$row.find('.asg-rate').val(),
            rate_tax:$row.find('.asg-ratetax').val(),
            quantity:$row.find('.asg-qty').val(),
            nonce:pjm_admin_vars.nonce
        }, function(res){
            if(res.success){ reload($row); } else alert('Błąd: '+res.data);
        });
    });

    $(document).off('submit.pjmasg', '.pjm-asg-form').on('submit.pjmasg', '.pjm-asg-form', function(e){
        e.preventDefault();
        var $form=$(this), data=$form.serializeArray();
        data.push({name:'action',value:'pjm_admin_assign_translator'});
        data.push({name:'order_id',value:$form.data('order')});
        data.push({name:'nonce',value:pjm_admin_vars.nonce});
        $.post(pjm_admin_vars.ajax_url, $.param(data), function(res){
            if(res.success){ reload($form); } else alert('Błąd: '+res.data);
        });
    });

    // (Materiały, miejsce i kontakt przeniesione na poziom ZAMÓWIENIA — brak edycji per-tłumacz.)

    $(document).off('click.pjmasgsend', '.pjm-asg-send').on('click.pjmasgsend', '.pjm-asg-send', function(){
        var aid=$(this).data('aid'), $b=$(this);
        if(!confirm('Wysłać brief zlecenia do tłumacza e-mailem?')) return;
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_send_assignment', assignment_id:aid, nonce:pjm_admin_vars.nonce }, function(res){
            if(res.success){ alert(res.data.message); reload($b); } else alert('Błąd: '+res.data);
        });
    });

    $(document).off('click.pjmasgdel', '.pjm-asg-del').on('click.pjmasgdel', '.pjm-asg-del', function(){
        var aid=$(this).data('aid'), $b=$(this), $wrap=$b.closest('.pjm-asg'), $row=$b.closest('.pjm-asg-card');
        if(!confirm('Usunąć to przypisanie?')) return;
        $b.prop('disabled', true);
        $.post(pjm_admin_vars.ajax_url, { action:'pjm_admin_remove_assignment', assignment_id:aid, nonce:pjm_admin_vars.nonce }, function(res){
            if(res && res.success){
                $row.css('opacity', .4).fadeOut(150, function(){ $(this).remove(); }); // natychmiast znika z widoku
                reload($wrap);                                                          // pełne odświeżenie (suma kosztów itd.)
            } else {
                $b.prop('disabled', false);
                alert('Błąd: ' + ((res && res.data) || 'nie udało się usunąć przypisania.'));
            }
        }).fail(function(){ $b.prop('disabled', false); alert('Błąd połączenia — spróbuj ponownie.'); });
    });

    // Przeliczenie startowe + filtr języków/roli na starcie.
    $('.pjm-asg-form').each(function(){ recalc($(this)); applyLangFilter($(this)); });
});
</script>
