<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Szablon widoku: Tłumaczenia Wideo PJM
 * Wersja: 7.1 (Fixed JS Config Mapping)
 */

$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

/* ===============================
   1. ŁADOWANIE DANYCH JSON
================================ */
$load_json_data = function($filename) {
    $paths = [
        defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'data/' . $filename : '',
        defined('PJM_CALC_PATH') ? PJM_CALC_PATH . 'includes/data/' . $filename : ''
    ];
    foreach ($paths as $path) {
        if (!empty($path) && file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
    }
    return [];
};

$video_data   = $load_json_data('calculator-video.json');
$packages     = $video_data['packages'] ?? [];

$addons_data  = $load_json_data('addons-video.json');
$addons_items = $addons_data['items'] ?? [];

/* ===============================
   2. KONFIGURACJA DLA JS (NAPRAWIONA)
================================ */
$js_config = [];
foreach ($packages as $pkg) {
    // Pobieramy ceny z configu
    $base_cost = $pkg['config']['tier_base_cost'] ?? $pkg['price']['amount'] ?? 0;
    $rate_unit = $pkg['config']['rate_per_unit'] ?? $pkg['price']['next_minute_price'] ?? 0;

    // !!! POPRAWKA TUTAJ: Pobieramy górną granicę z unit_range, a nie z nieistniejącego 'limit' !!!
    $limit_val = isset($pkg['unit_range'][1]) ? (int)$pkg['unit_range'][1] : 9999;

    $js_config[] = [
        'limit'      => $limit_val,
        'base'       => $base_cost,
        'name'       => $pkg['name'],
        'per_minute' => $rate_unit
    ];
}
?>

<div class="jm-pricing-section jm-wrapper">

    <header class="jm-seo-box">
        <span class="jm-badge-pill">FILMY W PJM</span>
        <div class="jm-header-area">
            <h1>Cennik i tłumaczenia nagrań wideo na PJM</h1>
        </div>
        <div class="jm-seo-desc">
            <p>
                Oferujemy profesjonalne tłumaczenia nagrań wideo, podcastów i wykładów na polski język migowy (PJM). 
                Realizacja w nowoczesnym studio z green screenem w jakości HD / 4K. 
                Sprawdź nasz <strong>cennik tłumaczeń PJM</strong> i skorzystaj z <strong>kalkulatora wyceny online</strong>.
            </p>
        </div>
    </header>

    <section class="jm-benefits-grid">
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">high_quality</span>
            <h4>Wysoka jakość</h4>
            <p>Profesjonalne studio, doświadczony tłumacz i oświetlenie studyjne.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">closed_caption</span>
            <h4>Napisy i montaż</h4>
            <p>Kompleksowa postprodukcja nagrań, w tym synchronizacja napisów.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">timer</span>
            <h4>Szybka realizacja</h4>
            <p>Gotowe tłumaczenia dostępne w pakietach już od 48 godzin.</p>
        </div>
    </section>

    <nav class="jm-tabs-nav">
        <button class="jm-tab-trigger active" data-target="vid-tab-packages">Tłumaczenie</button>
        <button class="jm-tab-trigger" data-target="vid-tab-addons">Dodatki</button>
    </nav>

    <div id="vid-tab-packages" class="jm-tab-content active">
        <div class="jm-auto-grid" style="--grid-cols: <?php echo count($packages); ?>;">
            <?php foreach ($packages as $pkg): 
                $is_featured = !empty($pkg['is_featured']);
                $is_tier_5 = ($pkg['id'] === 'tier_5'); // Sprawdzamy ID dla stylu poziomego
                
                // Klasy CSS
                $classes = ['jm-p-card'];
                if ($is_featured) $classes[] = 'featured';
                if ($is_tier_5)   $classes[] = 'premium-card'; 
                
                // Zakresy
                $range_start = (int)($pkg['unit_range'][0] ?? 1);
                $range_end   = (int)($pkg['unit_range'][1] ?? 1);
                
                // Ceny
                $base_cost = $pkg['config']['tier_base_cost'] ?? 0;
                $rate      = $pkg['config']['rate_per_unit'] ?? 0;

                // Helper do liczenia widełek
                $calc_pkg_price = function($mins, $start, $base, $r) {
                    $extra = max(0, $mins - ($start - 1));
                    return $base + ($extra * $r);
                };

                $price_min = $calc_pkg_price($range_start, $range_start, $base_cost, $rate);
                
                $is_unlimited = ($range_end >= 999);
                $price_string = '';
                $label_string = '';

                if ($is_unlimited) {
                    $price_string = 'od ' . number_format($price_min, 0, ',', ' ') . ' zł';
                    $label_string = 'wycena automatyczna';
                } elseif ($range_start === $range_end || $rate == 0) {
                    $price_string = number_format($price_min, 0, ',', ' ') . ' zł';
                    $label_string = 'cena za materiał';
                } else {
                    $price_max = $calc_pkg_price($range_end, $range_start, $base_cost, $rate);
                    $price_string = number_format($price_min, 0, ',', ' ') . ' – ' . number_format($price_max, 0, ',', ' ') . ' zł';
                    $label_string = 'zależnie od długości';
                }
            ?>
                <article class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                    
                    <?php if (!empty($pkg['badge'])): ?>
                        <div class="jm-badge-aio"><?php echo esc_html($pkg['badge']); ?></div>
                    <?php endif; ?>

                    <header class="jm-p-head">
                        <h3><?php echo esc_html($pkg['name']); ?></h3>
                        <span><?php echo esc_html($pkg['subtitle']); ?></span>
                    </header>

                    <div class="jm-p-price-box">
                        <span class="jm-price-num" style="<?php echo $is_tier_5 ? 'font-size:28px;' : ''; ?>">
                            <?php echo esc_html($price_string); ?>
                        </span>
                        <span class="jm-price-lbl"><?php echo esc_html($label_string); ?></span>
                    </div>

                    <div class="jm-feature-stack">
                        <?php foreach ($pkg['features'] as $f): ?>
                            <div class="jm-feature-row">
                                <span class="material-symbols-rounded" aria-hidden="true"><?php echo esc_html($f['icon']); ?></span>
                                <div class="jm-f-info">
                                    <span class="jm-f-val"><?php echo esc_html($f['text']); ?></span>
                                    <span class="jm-f-lbl"><?php echo esc_html($f['subtext']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="jm-order-btn" onclick="selectVideoPackage(<?php echo $range_start; ?>)">
                        Wybierz
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="vid-tab-addons" class="jm-tab-content">
        <div class="jm-auto-grid" style="--grid-cols: 2;">
            <?php foreach ($addons_items as $addon): ?>
                <div class="jm-addon-card">
                    <div class="jm-addon-info">
                        <h4><?php echo esc_html($addon['name']); ?></h4>
                        <p><?php echo esc_html($addon['description']); ?></p>
                    </div>
                    <div class="jm-addon-price">
                        <?php echo number_format($addon['price'], 0, ',', ' '); ?> zł
                        <small><?php echo ($addon['price_mode'] === 'per_unit') ? '/ min' : ''; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

	<div id="video-kalkulator" class="calculator-pricing" itemscope itemtype="https://schema.org/Service">
        <div class="jm-controls-row">
            <h2 style="margin:0; font-size:24px; color:var(--pjm-dark);"> Kalkulator wyceny tłumaczeń nagrań wideo, podcastów i wykładów na PJM </h2>
            <p style="margin-top:8px; font-size:14px; color:var(--pjm-gray);"> Oblicz koszt tłumaczenia swojego nagrania w polski język migowy. Wybierz długość nagrania i dodatki, aby uzyskać dokładną wycenę. </p>
        </div>

        <div class="jm-card-horizontal">
            <div class="jm-calc-left">
                <div>
                    <label class="jm-calc-label" for="video-calc-input">Długość nagrania (minuty)</label>
                    <input type="number" id="video-calc-input" min="1" max="60" step="1" value="1"
                           placeholder="Wpisz liczbę minut (do 60)..." itemprop="serviceOutput">
                    <small style="display:block; color:#888; font-size:12px; margin-top:4px;">Powyżej 60 min — napisz po wycenę indywidualną.</small>
                </div>

                <?php if (!empty($addons_items)): ?>
                    <div class="jm-addon-list">
                        <label class="jm-calc-label" style="font-size:14px;">Wybierz dodatki:</label>
                        <?php foreach ($addons_items as $addon): 
                            $price_desc = ($addon['price_mode'] === 'per_unit') ? 'zł / min' : 'zł';
                        ?>
                            <label class="jm-addon-row">
                                <input type="checkbox"
                                       class="jm-calc-addon-cb"
                                       data-price="<?php echo esc_attr($addon['price']); ?>"
                                       data-mode="<?php echo esc_attr($addon['price_mode']); ?>">
                                <div style="flex:1; display:flex; justify-content:space-between;">
                                    <span itemprop="additionalType"><?php echo esc_html($addon['name']); ?></span>
                                    <strong style="color:var(--pjm-orange);">+<?php echo $addon['price'] . ' ' . $price_desc; ?></strong>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="jm-calc-summary">
                <span class="jm-summary-label">Szacowany koszt</span>
                <div id="video-price-display" class="jm-summary-price" itemprop="price">0 zł</div>
                <div id="video-tier-name" class="jm-summary-tier" itemprop="serviceType">—</div>

                <?php if ($is_logged_in): ?>
                    <a href="<?php echo site_url('/moje-konto/?tab=calculator'); ?>" class="jm-order-btn" style="margin-top:20px;" itemprop="url">
                        Zamów wycenę
                    </a>
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

<script>
    var pjmVideoConfig = <?php echo json_encode($js_config); ?>;
</script>
<script src="<?php echo PJM_CALC_URL . 'assets/js/shortcode/video.js'; ?>"></script>
<?php
$pjm_seo_slug = 'nagrania';
$pjm_seo_tpl = PJM_CALC_PATH . 'templates/components/service-seo.php';
if ( file_exists( $pjm_seo_tpl ) ) include $pjm_seo_tpl;
?>
