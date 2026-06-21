<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcode: [subscription]
 * Wersja: 6.4 (Benefits + Footer Notes)
 */

$pricing_system = class_exists('PJM_Pricing') ? new PJM_Pricing() : null;
$plans = $pricing_system ? $pricing_system->get_plans() : [];
$yearly_months = $pricing_system ? ($pricing_system->get_setting('yearly_months_pay') ?? 11) : 11;

global $wpdb;
$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();
$current_plan_id = null;

if ( $is_logged_in ) {
    $table = $wpdb->prefix . 'pjm_subscriptions';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table ) {
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT plan_id FROM $table WHERE user_id = %d AND status='active' AND ends_at > NOW() ORDER BY id DESC LIMIT 1",
                $user_id
            )
        );
        $current_plan_id = $row ? $row->plan_id : null;
    }
}

// --- HELPERY ---
if (!function_exists('pjm_parse_loop_feature')) {
    function pjm_parse_loop_feature($loop_data) {
        if (!is_array($loop_data)) return $loop_data; 
        $type = $loop_data['type'] ?? '';
        if ($type === 'discount') return 'Rabat -' . (floatval($loop_data['value']) * 100) . '%';
        if ($type === 'free_token') return ($loop_data['days'] ?? 1) . ' dzień co ' . ($loop_data['frequency_months'] ?? 1) . ' msc';
        if ($type === 'full_support') return 'Pełne wsparcie';
        return 'Brak';
    }
}

if (!function_exists('pjm_parse_consultation')) {
    function pjm_parse_consultation($data) {
        if (!is_array($data)) return $data;
        $hours = intval($data['hours'] ?? 0);
        if ($hours === 0) return 'Brak';
        if ($hours >= 999) return 'Nielimitowane';
        return $hours . 'h / msc';
    }
}

if (!function_exists('pjm_render_public_btn')) {
    function pjm_render_public_btn($plan_slug, $is_logged_in, $current_plan_id) {
        if ( $is_logged_in ) {
            if ($plan_slug === $current_plan_id) {
                return '<button class="jm-order-btn current" disabled>Twój obecny plan</button>';
            }
            $url = site_url('/moje-konto/?tab=checkout&plan=' . $plan_slug . '&cycle=monthly');
            return '<a href="' . esc_url($url) . '" class="jm-order-btn pjm-checkout-link">Wybieram</a>';
        }
        return '<a href="' . site_url('/logowanie') . '" class="jm-order-btn">Zaloguj się</a>';
    }
}
?>

<div id="jm-modal-overlay" class="jm-modal-overlay" style="display:none;" role="dialog">
    <div class="jm-modal-content">
        <span class="jm-modal-close" onclick="closePjmModal()">&times;</span>
        <div class="jm-modal-header"><h3>Oferta Premium</h3></div>
        <div class="jm-modal-body"><?php echo do_shortcode('[contact-form-7 id="ddce477" title="Abonament"]'); ?></div>
    </div>
</div>

<section class="jm-pricing-section jm-wrapper">

    <header class="jm-seo-box">
        <span class="jm-badge-pill">STAŁA WSPÓŁPRACA B2B</span>
        <div class="jm-header-area">
            <h1>Abonament na tłumacza PJM dla firm – dostępność cyfrowa i informacyjna</h1>
        </div>
        <div class="jm-seo-desc">
            <p>
                Zapewnij swojej firmie pełną dostępność cyfrową i informacyjną zgodnie z obowiązującymi przepisami. 
                Jeden abonament obejmuje <strong>tłumaczenia wideo, tłumaczenia tekstów, wsparcie eksperta oraz wynajem pętli</strong>. 
                Idealny dla przedsiębiorstw i instytucji chcących zapewnić komunikację w polskim języku migowym (PJM).
            </p>
        </div>
    </header>

    <section class="jm-benefits-grid">
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">closed_caption</span>
            <h4>Darmowe napisy .SRT do materiałów</h4>
            <p>Każdy materiał tekstowy i filmowy w ramach abonamentu otrzymuje profesjonalne napisy w formacie .SRT, zapewniające dostępność treści dla osób słabosłyszących.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">swap_horiz</span>
            <h4>Mieszanie usług w abonamencie</h4>
            <p>Możesz dowolnie wymieniać Gesty między tłumaczeniami wideo, tekstowymi i obsługą wydarzeń, aby dopasować pakiet do potrzeb firmy.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">hourglass_empty</span>
            <h4>Gesty nie przepadają</h4>
            <p>Niewykorzystane Gesty przechodzą na kolejny miesiąc abonamentu, dzięki czemu możesz w pełni wykorzystać zakupione jednostki usług.</p>
        </div>
    </section>



        <div class="jm-controls-row">
            <div class="jm-billing-mode">
                <span class="jm-billing-txt" id="txt-monthly">Miesięcznie</span>
                <label class="jm-toggle-switch">
                    <input type="checkbox" id="jm-billing-input">
                    <span class="jm-slider"></span>
                </label>
                <span class="jm-billing-txt" id="txt-yearly">Rocznie <span class="jm-save-badge">-1 msc gratis</span></span>
            </div>
        </div>

        <div class="jm-grid-layout">
            <?php foreach ($plans as $slug => $plan): 
                $is_premium = ($slug === 'premium');
                $price = floatval($plan['price']);
                
                // Klasy CSS
                $classes = ['jm-p-card'];
                if (!empty($plan['is_featured']) || $slug === 'plus') $classes[] = 'featured';
                if ($is_premium) $classes[] = 'premium-card';

                // --- LOGIKA WARTOŚCI I LIMITÓW ---
                $raw_limit = $plan['limit_val'];
                $is_numeric_limit = (is_numeric($raw_limit) && floatval($raw_limit) < 999);
                
                $display_limit = $is_numeric_limit ? $raw_limit . ' Gesty' : 'Dopasowana ilość';
                $total_value_pln = 0;

                if ($is_numeric_limit) {
                    // 1. Gesty
                    $total_value_pln += floatval($raw_limit) * 500;
                    
                    // 2. Pętla
                    $loop_feat = $plan['features']['loop'] ?? [];
                    if (is_array($loop_feat) && ($loop_feat['type'] ?? '') === 'free_token') {
                        $days = intval($loop_feat['days'] ?? 1);
                        $freq = intval($loop_feat['frequency_months'] ?? 1);
                        if ($freq > 0) $total_value_pln += ($days * 400) / $freq;
                    }
                    
                    // 3. Konsultacje
                    $cons_hours = intval($plan['features']['consultation']['hours'] ?? 0);
                    if ($cons_hours > 0 && $cons_hours < 999) $total_value_pln += $cons_hours * 120;
                }

                $discount_percent = floatval($plan['overage_discount'] ?? 0) * 100;
            ?>
            <article class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                <?php if (!empty($plan['badge'])): ?><div class="jm-badge-aio"><?php echo esc_html($plan['badge']); ?></div><?php endif; ?>

                <header class="jm-p-head">
                    <h3><?php echo esc_html($plan['name']); ?></h3>
                    <span><?php echo esc_html($plan['subtitle']); ?></span>
                </header>

                <div class="jm-p-price-box">
                    <?php if ($price > 0): ?>
                        <span class="jm-price-num" data-base="<?php echo $price; ?>"><?php echo number_format($price, 0, ',', ' '); ?> zł</span>
                        <span class="jm-price-lbl">MSC</span>
                        <div class="jm-price-subtext" style="display:none;"></div>
                    <?php else: ?>
                        <span class="jm-price-num" style="font-size:28px;">WYCENA</span>
                        <span class="jm-price-lbl">INDYWIDUALNA</span>
                    <?php endif; ?>
                </div>

                <div class="jm-feature-stack">
                    <div class="jm-feature-row <?php echo ($is_numeric_limit && $total_value_pln > 0) ? 'highlight' : 'highlight-dark'; ?>">
                        <span class="material-symbols-rounded">token</span> 
                        <div class="jm-f-info">
                            <span class="jm-f-val"><?php echo esc_html($display_limit); ?></span>
                            <?php if($is_numeric_limit && $total_value_pln > 0): ?>
                                <span class="jm-f-lbl">WARTOŚĆ: <strong style="color:var(--pjm-success);"><?php echo number_format($total_value_pln, 0, ',', ' '); ?> ZŁ</strong></span>
                            <?php else: ?>
                                <span class="jm-f-lbl">DO USTALENIA</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="jm-feature-row">
                        <span class="material-symbols-rounded">timer</span>
                        <div class="jm-f-info">
                            <span class="jm-f-val"><?php echo esc_html($plan['time']); ?></span>
                            <span class="jm-f-lbl">CZAS REALIZACJI</span>
                        </div>
                    </div>

                    <div class="jm-feature-row">
                        <span class="material-symbols-rounded">hearing</span>
                        <div class="jm-f-info">
                            <span class="jm-f-val"><?php echo pjm_parse_loop_feature($plan['features']['loop']); ?></span>
                            <span class="jm-f-lbl">PĘTLA INDUKCYJNA</span>
                        </div>
                    </div>

                    <?php $cons_val = pjm_parse_consultation($plan['features']['consultation']); ?>
                    <div class="jm-feature-row <?php echo ($cons_val === 'Brak') ? 'jm-muted' : ''; ?>">
                        <span class="material-symbols-rounded">support_agent</span>
                        <div class="jm-f-info">
                            <span class="jm-f-val"><?php echo $cons_val; ?></span>
                            <span class="jm-f-lbl">WSPARCIE EKSPERTA</span>
                        </div>
                    </div>

                    <?php if(!empty($plan['features']['certificate'])): ?>
                    <div class="jm-feature-row">
                        <span class="material-symbols-rounded">verified</span>
                        <div class="jm-f-info">
                            <span class="jm-f-val">W cenie</span>
                            <span class="jm-f-lbl">ZAŚWIADCZENIE</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php
                    if ($is_premium) {
                        echo '<button class="jm-order-btn" onclick="openPjmModal(\'Premium\')">Zapytaj o ofertę</button>';
                    } else {
                        echo pjm_render_public_btn($slug, $is_logged_in, $current_plan_id);
                    }
                ?>
            </article>
            <?php endforeach; ?>
        </div>

    <?php
    if ( ! $is_logged_in ) {
        $cta_path = defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'templates/components/cta-login.php' : '';
        if ( file_exists( $cta_path ) ) include $cta_path;
    }
    ?>

    <footer class="jm-footer-area">

        <!-- Wydarzenia online / stacjonarne -->
        <div class="jm-notes-wrapper" style="margin-bottom: 40px;">
            <div class="jm-note-point" style="align-items:center;">
                <span class="material-symbols-rounded" style="font-size:32px; color:#1B5E4B; background:#FBF1DC; padding:10px; border-radius:12px;">wifi</span>
                <div>
                    <h6 style="margin:0;">Wydarzenia Online</h6>
                    <p style="margin:0; font-size:13px;">W każdym pakiecie abonamentowym bez limitów.</p>
                </div>
            </div>
            <div class="jm-note-point" style="align-items:center;">
                <span class="material-symbols-rounded" style="font-size:32px; color:#1B5E4B; background:#FBF1DC; padding:10px; border-radius:12px;">location_on</span>
                <div>
                    <h6 style="margin:0;">Wydarzenia Stacjonarne</h6>
                    <p style="margin:0; font-size:13px;">Pakiety Podstawowy, Standard, Plus: Warszawa | Pakiet Pro i Premium: cała Polska</p>
                </div>
            </div>
        </div>

        <!-- FAQ – struktura pod SEO -->
        <div class="jm-faq-container">
            <h2 style="text-align:center; margin-bottom:30px;">FAQ – Najczęściej zadawane pytania dotyczące abonamentu PJM</h2>
            <div class="jm-faq-grid">
                
                <div class="jm-faq-item">
                    <h5>Czy abonament jest dostępny dla osób prywatnych?</h5>
                    <p>Nie. Abonament jest usługą B2B, przeznaczoną wyłącznie dla firm i podmiotów gospodarczych.</p>
                </div>

                <div class="jm-faq-item">
                    <h5>Czy mogę zrezygnować z abonamentu w trakcie jego trwania?</h5>
                    <p>Abonament zawierany jest na czas określony. Rezygnacja w trakcie trwania okresu rozliczeniowego nie zwalnia z obowiązku zapłaty za cały okres.</p>
                </div>

                <div class="jm-faq-item">
                    <h5>Czy niewykorzystane Gesty przepadają?</h5>
                    <p>Gesty nie przepadają w trakcie aktywnego abonamentu. Po zakończeniu umowy Abonent ma 30 dni na ich wykorzystanie.</p>
                </div>

                <div class="jm-faq-item">
                    <h5>Ile wynosi wartość jednego Gestu?</h5>
                    <p>1 Gest odpowiada wartości 500 zł brutto.</p>
                </div>

                <div class="jm-faq-item">
                    <h5>Czy abonament odnawia się automatycznie?</h5>
                    <p>Tak, abonament odnawia się automatycznie na kolejny okres rozliczeniowy.</p>
                </div>

                <div class="jm-faq-item">
                    <h5>Jak szybko realizowane są usługi?</h5>
                    <p>Czas realizacji zależy od wybranego pakietu abonamentowego i jest określony w ofercie.</p>
                </div>

                <div class="jm-faq-item" style="grid-column: 1 / -1;">
                    <h5>Czy mogę udostępniać abonament innym podmiotom?</h5>
                    <p>Nie, chyba że Usługodawca wyrazi na to pisemną zgodę.</p>
                </div>

            </div>
        </div>

    </footer>

</section>

<script src="<?php echo PJM_CALC_URL . 'assets/js/shortcode/subscription.js'; ?>"></script>
<script>
    if(typeof window.pjmUpdatePricing === 'function'){ window.pjmUpdatePricing(); }
</script>