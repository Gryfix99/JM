<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Szablon widoku: Tłumaczenia Eventowe (Unified Design)
 * Lokalizacja: templates/shortcodes/event.php
 */

$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

/* ===============================
   1. ŁADOWANIE DANYCH
================================ */
$load_json = function($filename) {
    $paths = [
        defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'includes/data/' . $filename : '',
        defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'data/' . $filename : ''
    ];
    foreach ($paths as $path) {
        if (!empty($path) && file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
    }
    return [];
};

$event_data   = $load_json('calculator-event.json');
$addons_data  = $load_json('addons-event.json');
$packages     = $event_data['packages'] ?? [];
$global_rules = $event_data['pricing_rules'] ?? [
    'translators_threshold' => 1,
    'translators_multiplier' => 2
];
$addons_items = $addons_data['items'] ?? [];

$first_pkg    = reset($packages);
$base_rate_1h = $first_pkg['config']['rate_per_translator'] ?? 200;
?>

<div class="jm-pricing-section jm-wrapper">

    <header class="jm-seo-box">
        <span class="jm-badge-pill">TŁUMACZENIA NA ŻYWO</span>
        <div class="jm-header-area">
            <!-- H1 zoptymalizowane pod SEO -->
            <h1>Profesjonalne tłumaczenia wydarzeń i konferencji na polski język migowy (PJM)</h1>
        </div>
        <div class="jm-seo-desc">
            <p>
                Zapewniamy profesjonalną komunikację w <strong>polskim języku migowym (PJM)</strong> podczas konferencji, szkoleń i spotkań biznesowych. 
                Nasi tłumacze posiadają certyfikaty lub inne równoważne dokumenty poświadczające kompetencje. 
                Powyżej 1 godziny wydarzenia pracujemy w zespole minimum 2 tłumaczy, aby zapewnić pełną ciągłość tłumaczenia.
            </p>
        </div>
    </header>

    <section class="jm-benefits-grid">
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">license</span>
            <h4>Certyfikowany tłumacz PJM</h4>
            <p>Naturalny przekład wydarzenia wykonany przez doświadczonego, certyfikowanego tłumacza.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">group</span>
            <h4>Zespół tłumaczy</h4>
            <p>Przy dłuższych wydarzeniach pracuje zespół tłumaczy, aby zapewnić ciągłość i komfort uczestników.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">policy</span>
            <h4>Zgodność z Kodeksem Etycznym</h4>
            <p>Tłumaczenia zgodne z Kodeksem Etycznym Tłumaczy PJM, zapewniające profesjonalizm i poufność.</p>
        </div>
    </section>


    <nav class="jm-tabs-nav">
        <button class="jm-tab-trigger active" data-target="event-tab-packages">Tłumaczenie</button>
        <button class="jm-tab-trigger" data-target="event-tab-addons">Dodatki</button>
    </nav>

    <section id="event-tab-packages" class="jm-tab-content active">
        <div class="jm-auto-grid" style="--grid-cols: <?php echo count($packages); ?>;">
            <?php foreach ($packages as $pkg): 
                $is_featured = !empty($pkg['is_featured']);
                $pkg_class = $is_featured ? 'featured' : '';
                
                $range = $pkg['unit_range'] ?? [1, 1];

                // Prosta symulacja ceny dla wyświetlania
                $calc_price = function($h) use ($pkg, $base_rate_1h, $global_rules) {
                    $rate = $pkg['config']['rate_per_translator'];
                    $cost = ($h === 1)
                        ? $base_rate_1h
                        : $base_rate_1h + (($h - 1) * $rate);

                    $mult = ($h > $global_rules['translators_threshold'])
                        ? $global_rules['translators_multiplier']
                        : 1;

                    return $cost * $mult;
                };

                $p_min = $calc_price($range[0]);
                $price_display = $pkg['price_display'] ?? 'range';

                if ($price_display === 'from') {
                    // Próg otwarty (np. konferencja kilkudniowa) — cena „od", bez absurdalnego maksimum
                    $price_label = 'od ' . number_format($p_min, 0, ',', ' ') . ' zł';
                } elseif ($range[0] !== $range[1]) {
                    $p_max = $calc_price($range[1]);
                    $price_label = number_format($p_min, 0, ',', ' ') . ' – ' . number_format($p_max, 0, ',', ' ') . ' zł';
                } else {
                    $price_label = number_format($p_min, 0, ',', ' ') . ' zł';
                }
            ?>
                <?php // Próg „od" (Konferencja kilkudniowa) renderujemy jako POZIOMY PASEK (premium-card), spójnie z video.php tier_5. ?>
                <article class="jm-p-card <?php echo esc_attr($pkg_class); ?><?php echo ( $price_display === 'from' ) ? ' premium-card' : ''; ?>">

                    <?php if (!empty($pkg['badge'])): ?>
                        <div class="jm-badge-aio"><?php echo esc_html($pkg['badge']); ?></div>
                    <?php endif; ?>

                    <header class="jm-p-head">
                        <h3><?php echo esc_html($pkg['name']); ?></h3>
                        <span><?php echo esc_html($pkg['subtitle']); ?></span>
                    </header>

                    <div class="jm-p-price-box">
                        <span class="jm-price-num"><?php echo esc_html($price_label); ?></span>
                        <span class="jm-price-lbl"><?php echo esc_html($pkg['unit_label']); ?></span>
                    </div>

                    <div class="jm-feature-stack">
                        <?php foreach ($pkg['features'] as $f): ?>
                            <div class="jm-feature-row">
                                <span class="material-symbols-rounded"><?php echo esc_html($f['icon']); ?></span>
                                <div class="jm-f-info">
                                    <span class="jm-f-val"><?php echo esc_html($f['text']); ?></span>
                                    <span class="jm-f-lbl"><?php echo esc_html($f['subtext']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="jm-order-btn" onclick="selectEventPackage(<?php echo (int)$range[0]; ?>)">
                        Wybierz
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="event-tab-addons" class="jm-tab-content">
        <div class="jm-auto-grid" style="--grid-cols: 2;">
            <?php foreach ($addons_items as $addon): ?>
                <div class="jm-addon-card">
                    <div class="jm-addon-info">
                        <h4><?php echo esc_html($addon['name']); ?></h4>
                        <p><?php echo esc_html($addon['description']); ?></p>
                    </div>
                    <div class="jm-addon-price">
                        <?php echo number_format($addon['price'], 0, ',', ' '); ?> zł
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div id="event-kalkulator" class="calculator-pricing pjm-event-form" itemscope itemtype="https://schema.org/Service">
        
        <div class="jm-controls-row">
            <h2 style="margin:0; font-size:24px; color:var(--pjm-dark);">
                Kalkulator wyceny tłumaczeń wydarzeń na żywo w PJM
            </h2>
            <p style="margin-top:8px; font-size:14px; color:var(--pjm-gray);">
                Oblicz koszt tłumaczenia konferencji, szkoleń i innych wydarzeń na polski język migowy. Wybierz tryb (stacjonarnie lub online), liczbę godzin i dodatki.
            </p>
        </div>

        <div class="jm-card-horizontal">

            <div class="jm-calc-left">

                <!-- Tryb wydarzenia -->
                <div style="display:flex; gap:15px; margin-bottom:20px;">
                    <label class="jm-addon-row" style="flex:1; justify-content:center;">
                        <input type="radio" name="event_mode" value="onsite" checked style="accent-color:var(--pjm-orange);" itemprop="serviceOutput" class="pjm-mode-radio">
                        <span style="font-weight:600;">Stacjonarnie</span>
                    </label>
                    <label class="jm-addon-row" style="flex:1; justify-content:center;">
                        <input type="radio" name="event_mode" value="online" style="accent-color:var(--pjm-orange);" itemprop="serviceOutput" class="pjm-mode-radio">
                        <span style="font-weight:600;">Online</span>
                    </label>
                </div>

                <!-- Dni i godziny -->
                <label class="jm-calc-label">Terminy i godziny wydarzenia:</label>
                <div id="event-days-container"></div>
                <button type="button" id="btn-add-event-day" style="margin-top:10px; padding:10px 15px; background:var(--pjm-orange); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">+ Dodaj kolejny dzień</button>

                <!-- Dodatki -->
                <div class="jm-calc-addons" style="margin-top:25px;">
                    <label class="jm-calc-label" style="font-size:14px; margin-top:15px;">Wybierz dodatki:</label>
                    <?php foreach ($addons_items as $addon): ?>
                        <label class="jm-addon-row pjm-addon-row">
                            <input type="checkbox"
                                class="jm-calc-addon-cb pjm-addon-checkbox"
                                data-id="<?php echo esc_attr($addon['id'] ?? sanitize_title($addon['name'])); ?>"
                                data-price="<?php echo esc_attr($addon['price']); ?>"
                                data-mode="<?php echo esc_attr($addon['price_mode']); ?>"
                                itemprop="additionalType">
                            <div style="flex:1; display:flex; justify-content:space-between; margin-left:10px;">
                                <span><?php echo esc_html($addon['name']); ?></span>
                                <strong style="color:var(--pjm-orange);">+<?php echo $addon['price']; ?> zł</strong>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

            </div>

            <div class="jm-calc-summary">
                <span class="jm-summary-label">Szacowany koszt tłumaczenia</span>
                <div id="event-price-display" class="jm-summary-price" itemprop="price">0 zł</div>
                <div id="event-tier-name" class="jm-summary-tier" itemprop="serviceType">—</div>
                
                <!-- Podsumowanie JS -->
                <div id="event-summary-content" style="margin-top:15px; font-size:13px; color:#555;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span>Czas trwania:</span> <strong id="event-summary-time">0h</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span>Dni:</span> <strong id="event-summary-days">0</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span>Tłumacze:</span> <strong id="event-summary-translators">0 os.</strong>
                    </div>
                </div>

                <div id="event-hourly-rate" style="font-size:12px; color:#999; margin-top:5px; text-align:right;"></div>
                <div id="event-addons-list" style="margin-top:15px; padding-top:15px; border-top:1px solid #eee;"></div>

                <?php if ($is_logged_in): ?>
                    <button type="button" id="btn-add-event" class="jm-order-btn" style="margin-top:20px; width:100%;" itemprop="url">
                        Dodaj do koszyka
                    </button>
                <?php else: ?>
                    <div class="jm-login-hint">
                        <a href="<?php echo site_url('/logowanie'); ?>">Zaloguj się</a>, aby zamówić.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    
    <?php
    if ( ! $is_logged_in ) {
        $cta_path = defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'templates/components/cta-login.php' : '';
        if ( file_exists( $cta_path ) ) include $cta_path;
    }
    ?>

</div>

<!-- Kontener z konfiguracją Tiers dla pliku JS (zapełnij poprawnymi danymi w PHP lub zostaw z atrybutem dataset) -->
<div id="pjm-event-tiers-data" data-tiers='<?php echo json_encode($packages); ?>' data-travel-rate="7" style="display:none;"></div>

<script>
    var pjmEventPackages = <?php echo json_encode($packages); ?>;
    var pjmGlobalRules  = <?php echo json_encode($global_rules); ?>;
    var pjmBaseRate1h   = <?php echo (int)$base_rate_1h; ?>;
</script>
<?php /* event.js jest ładowany przez wp_enqueue_script('pjm-shortcode-event-js') w class-assets.php.
        NIE dołączamy go ponownie inline — podwójne ładowanie podpinało handler 2× i „+ Dodaj dzień" dodawał 2 wiersze. */ ?>
<?php
$pjm_seo_slug = 'na-zywo';
$pjm_seo_tpl = PJM_CALC_PATH . 'templates/components/service-seo.php';
if ( file_exists( $pjm_seo_tpl ) ) include $pjm_seo_tpl;
?>
