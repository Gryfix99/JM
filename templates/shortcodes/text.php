<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Szablon widoku: Tłumaczenia Tekstów PJM
 * Wersja: 2.2 (Uporządkowana struktura z sekcją dodatków)
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

$text_data   = $load_json_data('calculator-text.json');
$packages    = $text_data['packages'] ?? [];

$addons_data = $load_json_data('addons-text.json');
$addons      = $addons_data['items'] ?? [];

/* ===============================
   2. KONFIGURACJA DLA JS
================================ */
$js_config = [];

foreach ($packages as $pkg) {
    $base_cost = $pkg['config']['tier_base_cost'] ?? 0;
    $rate_unit = $pkg['config']['rate_per_unit'] ?? 0;
    $limit_val = isset($pkg['unit_range'][1]) ? (int)$pkg['unit_range'][1] : 9999;

    $js_config[] = [
        'limit'     => $limit_val,
        'base'      => $base_cost,
        'name'      => $pkg['name'],
        'per_unit'  => $rate_unit
    ];
}
?>

<div class="jm-pricing-section jm-wrapper">
    
    <header class="jm-seo-box">
        <span class="jm-badge-pill">TŁUMACZENIA TEKSTÓW NA PJM</span>
        <div class="jm-header-area">
            <h1>Profesjonalne tłumaczenie tekstów na polski język migowy (PJM)</h1>
        </div>
        <div class="jm-seo-desc">
            <p>
                Przekładamy teksty pisane na <strong>polski język migowy (PJM)</strong> w formie nagrania wideo. 
                Tłumaczymy między innymi <strong>informacje publiczne, wystawy oraz dokumenty urzędowe</strong>. 
                Każda strona przeliczeniowa odpowiada <strong>1800 znaków ze spacjami (ZZS)</strong>. 
                Nagrania mogą być dodatkowo opatrzone <strong>napisami SRT</strong>, co ułatwia dostępność treści.
            </p>
        </div>
    </header>

    <section class="jm-benefits-grid">
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">license</span>
            <h4>Gwarancja jakości tłumaczeń PJM</h4>
            <p>Tłumaczenia wykonywane przez certyfikowanych tłumaczy PJM, z zachowaniem poprawności językowej i merytorycznej.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">high_quality</span>
            <h4>Profesjonalna jakość obrazu</h4>
            <p>Nagrania wideo w jakości HD/4K, realizowane w profesjonalnym studio z green screenem.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">library_books</span>
            <h4>Kompleksowa realizacja</h4>
            <p>Otrzymujesz gotowy plik wideo po montażu, z możliwością dodania napisów SRT dla pełnej dostępności treści.</p>
        </div>
    </section>

    <nav class="jm-tabs-nav">
        <button class="jm-tab-trigger active" data-target="txt-tab-packages">Tłumaczenie</button>
        <button class="jm-tab-trigger" data-target="txt-tab-addons">Dodatki</button>
    </nav>

    <div id="txt-tab-packages" class="jm-tab-content active">
        <div class="jm-auto-grid" style="--grid-cols: <?php echo count($packages); ?>;">

            <?php foreach ($packages as $pkg):
                $is_featured = !empty($pkg['is_featured']);
                $is_tier_5   = ($pkg['id'] === 'tier_5');

                $classes = ['jm-p-card'];
                if ($is_featured) $classes[] = 'featured';
                if ($is_tier_5)   $classes[] = 'premium-card';

                $range_start = (int)($pkg['unit_range'][0] ?? 1);
                $range_end   = (int)($pkg['unit_range'][1] ?? 1);

                $base_cost = $pkg['config']['tier_base_cost'] ?? 0;
                $rate      = $pkg['config']['rate_per_unit'] ?? 0;

                // ALGORYTM OBLICZENIOWY
                $calc_pkg_price = function($units, $start, $base, $r) {
                    $extra = max(0, $units - ($start - 1));
                    return $base + ($extra * $r);
                };

                $price_min = $calc_pkg_price($range_start, $range_start, $base_cost, $rate);
                $is_unlimited = ($range_end >= 999);

                if ($is_unlimited) {
                    $price_string = 'od ' . number_format($price_min, 0, ',', ' ') . ' zł';
                    $label_string = 'wycena automatyczna';
                } elseif ($range_start === $range_end || $rate == 0) {
                    $price_string = number_format($price_min, 0, ',', ' ') . ' zł';
                    $label_string = 'cena za tekst';
                } else {
                    $price_max = $calc_pkg_price($range_end, $range_start, $base_cost, $rate);
                    $price_string = number_format($price_min, 0, ',', ' ') . ' – ' . number_format($price_max, 0, ',', ' ') . ' zł';
                    $label_string = 'zależnie od liczby stron';
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
                            <span class="material-symbols-rounded"><?php echo esc_html($f['icon']); ?></span>
                            <div class="jm-f-info">
                                <span class="jm-f-val"><?php echo esc_html($f['text']); ?></span>
                                <span class="jm-f-lbl"><?php echo esc_html($f['subtext']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="jm-order-btn" onclick="selectTextPackage(<?php echo $range_start; ?>)">
                    Wybierz
                </button>
            </article>

            <?php endforeach; ?>
        </div>
    </div>

    <section id="txt-tab-addons" class="jm-tab-content">
        <div class="jm-auto-grid" style="--grid-cols: 2;">
            <?php foreach ($addons as $addon): ?>
                <div class="jm-addon-card">
                    <div class="jm-addon-info">
                        <h4><?php echo esc_html($addon['name']); ?></h4>
                        <p><?php echo esc_html($addon['description']); ?></p>
                    </div>
                    <div class="jm-addon-price">
                        <strong><?php echo number_format($addon['price'], 0, ',', ' '); ?> zł</strong>
                        <small><?php echo $addon['price_mode'] === 'per_unit' ? ' / strona' : ''; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div id="text-kalkulator" class="calculator-pricing">
        <div class="jm-card-horizontal">
            <div class="jm-calc-left">

                <div class="jm-calc-group">
                    <label class="jm-calc-label" for="text-calc-content">Wklej tekst do wyceny</label>
                    <textarea id="text-calc-content" rows="6" placeholder="Wklej tutaj treść do tłumaczenia..."></textarea>
                    <small style="color:#999; display:block; margin-top:5px;">
                        System automatycznie policzy liczbę znaków ze spacjami (ZZS).
                    </small>
                </div>

                <div class="jm-calc-group" style="margin-top:20px;">
                    <label class="jm-calc-label" for="text-calc-input">Lub podaj liczbę znaków (ZZS)</label>
                    <input type="number" id="text-calc-input" min="1" value="1800" placeholder="Wpisz ilość znaków...">
                    <small style="color:#999; display:block; margin-top:5px;">
                        1 strona = 1800 znaków ze spacjami
                    </small>
                </div>

                <?php if (!empty($addons)): ?>
                    <div class="jm-addon-list">
                        <label class="jm-calc-label" style="font-size:14px;">Wybierz dodatki do wyceny:</label>
                        <?php foreach ($addons as $addon): 
                            $price_desc = ($addon['price_mode'] === 'per_unit') ? 'zł / strona' : 'zł';
                        ?>
                            <label class="jm-addon-row">
                                <input type="checkbox" class="jm-calc-addon-cb"
                                    data-price="<?php echo esc_attr($addon['price']); ?>"
                                    data-mode="<?php echo esc_attr($addon['price_mode']); ?>">
                                <div style="flex:1; display:flex; justify-content:space-between;">
                                    <span><?php echo esc_html($addon['name']); ?></span>
                                    <strong style="color:var(--pjm-orange);">
                                        +<?php echo $addon['price'] . ' ' . $price_desc; ?>
                                    </strong>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="jm-calc-summary">
                <span class="jm-summary-label">Szacowany koszt</span>
                <div id="text-price-display" class="jm-summary-price">0 zł</div>
                <div id="text-tier-name" class="jm-summary-tier">—</div>

                <?php if ($is_logged_in): ?>
                    <a href="<?php echo site_url('/moje-konto/?tab=calculator'); ?>" class="jm-order-btn" style="margin-top:20px;">
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
</div>

<script>
    var pjmTextConfig = <?php echo json_encode($js_config); ?>;
</script>
<script src="<?php echo PJM_CALC_URL . 'assets/js/shortcode/text.js'; ?>"></script>
<?php
$pjm_seo_slug = 'teksty';
$pjm_seo_tpl = PJM_CALC_PATH . 'templates/components/service-seo.php';
if ( file_exists( $pjm_seo_tpl ) ) include $pjm_seo_tpl;
?>
