<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Szablon widoku: Wynajem Pętli Indukcyjnych (Unified Design)
 * Lokalizacja: templates/shortcodes/loop.php
 */

$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

/* ===============================
   1. ŁADOWANIE DANYCH
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

$loop_data   = $load_json_data('calculator-loop.json');
$addons_data = $load_json_data('addons-loop.json');

$packages    = $loop_data['packages'] ?? [];
$addons      = $addons_data['items'] ?? [];
$config      = $loop_data['pricing_rules'] ?? ['unit_name' => 'dzień'];

/* ===============================
   2. KONFIGURACJA DLA JS
================================ */
// Przekazanie zmiennych do JS odbywa się na końcu pliku
?>

<div class="jm-pricing-section jm-wrapper">

    <header class="jm-seo-box">
        <span class="jm-badge-pill">DOSTĘPNOŚĆ DLA OSÓB SŁABOSŁYSZĄCYCH</span>
        <div class="jm-header-area">
            <!-- H1 zoptymalizowane pod SEO -->
            <h1>Wynajem mobilnych pętli indukcyjnych dla sal konferencyjnych, urzędów i wydarzeń</h1>
        </div>
        <div class="jm-seo-desc">
            <p>
                Oferujemy mobilne pętle indukcyjne zgodne z normą <strong>IEC 60118-4</strong>. 
                Doskonałe dla osób korzystających z aparatów słuchowych, zapewniają równomierny odbiór dźwięku w salach konferencyjnych, urzędach i na wydarzeniach. 
                Szybki montaż i test urządzeń gwarantuje pełną gotowość przed rozpoczęciem wydarzenia.
            </p>
        </div>
    </header>

    <section class="jm-benefits-grid">
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">wifi</span>
            <h4>Zasięg do 150 m²</h4>
            <p>Równomierne pokrycie sali i pełna dostępność dźwięku dla uczestników.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">hearing</span>
            <h4>Dla aparatów słuchowych</h4>
            <p>Bezpośredni sygnał audio dla osób ze wspomaganiem słuchu.</p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">engineering</span>
            <h4>Szybki montaż i konfiguracja</h4>
            <p>Instalacja i test urządzeń przed wydarzeniem – pełna gotowość w krótkim czasie.</p>
        </div>
    </section>


    <nav class="jm-tabs-nav">
        <button class="jm-tab-trigger active" data-target="loop-tab-packages">Pętla indukcyjna</button>
        <button class="jm-tab-trigger" data-target="loop-tab-addons">Dodatki</button>
    </nav>

    <section id="loop-tab-packages" class="jm-tab-content active">
        <div class="jm-auto-grid" style="--grid-cols: <?php echo count($packages); ?>;">
            <?php foreach ($packages as $pkg): 
                $is_featured = !empty($pkg['is_featured']);
                $pkg_class = $is_featured ? 'featured' : '';
                $min_days = (int)($pkg['unit_range'][0] ?? 1);
            ?>
                <article class="jm-p-card <?php echo esc_attr($pkg_class); ?>">

                    <?php if ($is_featured): ?>
                        <div class="jm-badge-aio"><?php echo esc_html($pkg['badge']); ?></div>
                    <?php endif; ?>

                    <header class="jm-p-head">
                        <h3><?php echo esc_html($pkg['name']); ?></h3>
                        <span><?php echo esc_html($pkg['subtitle']); ?></span>
                    </header>

                    <div class="jm-p-price-box">
                        <span class="jm-price-num">
                            <?php echo number_format($pkg['config']['rate_per_unit'], 0, ',', ' '); ?> zł
                        </span>
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

                    <button class="jm-order-btn" onclick="selectLoopDays(<?php echo $min_days; ?>)">
                        Wybierz
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="loop-tab-addons" class="jm-tab-content">
        <div class="jm-auto-grid" style="--grid-cols: 3;">
            <?php if ($addons): foreach ($addons as $addon): ?>
                <div class="jm-addon-card">
                    <div class="jm-addon-info">
                        <h4><?php echo esc_html($addon['name']); ?></h4>
                        <p><?php echo esc_html($addon['description']); ?></p>
                    </div>
                    <div class="jm-addon-price">
                        <strong><?php echo number_format($addon['price'],0,',',' '); ?> zł</strong>
                        <span><?php echo $addon['price_mode'] === 'per_unit' ? 'za dzień' : 'sztuka'; ?></span>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <p style="text-align:center; width:100%; color:#999;">Brak dodatkowych akcesoriów.</p>
            <?php endif; ?>
        </div>
    </section>

    <div id="loop-kalkulator" class="calculator-pricing" itemscope itemtype="https://schema.org/Service">

        <div class="jm-controls-row">
            <h2 style="margin:0; font-size:24px; color:var(--pjm-dark);">
                Kalkulator wyceny wynajmu mobilnych pętli indukcyjnych
            </h2>
            <p style="margin-top:8px; font-size:14px; color:var(--pjm-gray);">
                Oblicz koszt wynajmu pętli indukcyjnych na konferencje, wydarzenia lub do urzędów. Wybierz liczbę dni wynajmu i dodatkowe akcesoria.
            </p>
        </div>

        <div class="jm-card-horizontal">

            <div class="jm-calc-left">
                <div>
                    <label class="jm-calc-label" for="loop-range-days">Liczba dni wynajmu</label>
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:10px;">
                        <input type="number" id="loop-input-days" min="1" max="30" value="1" style="width:80px;">
                    </div>
                </div>

                <?php if ($addons): ?>
                    <div class="jm-addon-list">
                        <label class="jm-calc-label" style="font-size:14px;">Wybierz akcesoria do wynajmu:</label>
                        <?php foreach ($addons as $addon): ?>
                            <label class="jm-addon-row">
                                <input type="checkbox"
                                    class="jm-calc-addon-cb jm-loop-addon-cb"
                                    data-id="<?php echo esc_attr($addon['id'] ?? sanitize_title($addon['name'])); ?>"
                                    data-price="<?php echo esc_attr($addon['price']); ?>"
                                    data-mode="<?php echo esc_attr($addon['price_mode']); ?>"
                                    itemprop="additionalType">
                                <div style="flex:1; display:flex; justify-content:space-between;">
                                    <span><?php echo esc_html($addon['name']); ?></span>
                                    <strong style="color:var(--pjm-orange);">+<?php echo $addon['price']; ?> zł</strong>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="jm-calc-summary">
                <span class="jm-summary-label">Przewidywany koszt wynajmu</span>
                <div id="loop-price-display" class="jm-summary-price" itemprop="price">0 zł</div>
                <div id="loop-tier-badge" class="jm-summary-tier" itemprop="serviceType">—</div>

                <?php if ($is_logged_in): ?>
                    <button id="btn-loop-order" class="jm-order-btn" style="margin-top:20px;" itemprop="url">Zamów teraz</button>
                <?php else: ?>
                    <a href="<?php echo site_url('/logowanie'); ?>" class="jm-order-btn" style="margin-top:20px;" itemprop="url">Zaloguj się</a>
                <?php endif; ?>

                <small style="display:block; margin-top:15px; color:#999; font-size:11px;">
                    Cena nie zawiera kosztów transportu.
                </small>
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
    var pjmLoopPackages = <?php echo json_encode($packages); ?>;
    var pjmLoopAddons   = <?php echo json_encode($addons); ?>;
    var pjmLoopConfig   = <?php echo json_encode($config); ?>;
</script>
<script src="<?php echo PJM_CALC_URL . 'assets/js/shortcode/loop.js'; ?>"></script>
<?php
$pjm_seo_slug = 'petla-indukcyjna';
$pjm_seo_tpl = PJM_CALC_PATH . 'templates/components/service-seo.php';
if ( file_exists( $pjm_seo_tpl ) ) include $pjm_seo_tpl;
?>
