<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Szablon: Mój Kalkulator (SPA - Single Page Application Layout)
 * Wersja: 19.3 (Full Integration with JS Core)
 */

// 1. Zabezpieczenie stałej ścieżki
if ( ! defined( 'PJM_CALC_PATH' ) ) {
    echo '<div class="pjm-error" style="color:red; padding:20px;">Błąd krytyczny: Stała PJM_CALC_PATH nie jest zdefiniowana.</div>';
    return;
}

global $wpdb;
$user_id = get_current_user_id();

// 2. Konfiguracja awaryjna (Fallback)
// Te dane zostaną użyte w JS, jeśli class-assets.php nie załaduje ich poprawnie.
$calc_config_fallback = array(
    'ajax_url'     => admin_url('admin-ajax.php'),
    'nonce'        => wp_create_nonce('pjm_calc_nonce'), // Token do operacji koszyka
    'checkout_url' => site_url('/moje-konto/?tab=checkout'),
    'site_url'     => site_url(),
    'pdf_worker'   => 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'
);
?>

<script type="text/javascript">
    /* <![CDATA[ */
    if (typeof pjm_calc_vars === 'undefined') {
        var pjm_calc_vars = <?php echo json_encode($calc_config_fallback); ?>;
        console.warn('PJM System: Załadowano konfigurację awaryjną (Inline).');
    }
    /* ]]> */
</script>

<div class="pjm-calc-wrapper" id="pjm-calculator-app">
    
    <div class="pjm-tabs-wrapper">
        <button type="button" class="pjm-tab-btn active" data-tab="video" onclick="PJM_Core.switchTab('video', event)">
            <span class="material-symbols-rounded">videocam</span> Wideo
        </button>
        <button type="button" class="pjm-tab-btn" data-tab="text" onclick="PJM_Core.switchTab('text', event)">
            <span class="material-symbols-rounded">description</span> Tekst
        </button>
        <button type="button" class="pjm-tab-btn" data-tab="event" onclick="PJM_Core.switchTab('event', event)">
            <span class="material-symbols-rounded">groups</span> Wydarzenia
        </button>
        <button type="button" class="pjm-tab-btn" data-tab="loop" onclick="PJM_Core.switchTab('loop', event)">
            <span class="material-symbols-rounded">hearing</span> Pętla
        </button>
    </div>

    <div class="pjm-app-grid">
        
        <div class="pjm-app-content">
            
            <div id="tab-video" class="pjm-tab-content active">
                <?php 
                $path = PJM_CALC_PATH . 'templates/calculators/video-form.php';
                if(file_exists($path)) include $path; 
                else echo '<div class="pjm-alert error">Błąd: Brak pliku video-form.php</div>';
                ?>
            </div>

            <div id="tab-text" class="pjm-tab-content" style="display:none;">
                <?php 
                $path = PJM_CALC_PATH . 'templates/calculators/text-form.php';
                if(file_exists($path)) include $path; 
                else echo '<div class="pjm-alert error">Błąd: Brak pliku text-form.php</div>';
                ?>
            </div>

            <div id="tab-event" class="pjm-tab-content" style="display:none;">
                <?php 
                $path = PJM_CALC_PATH . 'templates/calculators/event-form.php';
                if(file_exists($path)) include $path; 
                else echo '<div class="pjm-alert error">Błąd: Brak pliku event-form.php</div>';
                ?>
            </div>

            <div id="tab-loop" class="pjm-tab-content" style="display:none;">
                <?php 
                $path = PJM_CALC_PATH . 'templates/calculators/loop-form.php';
                if(file_exists($path)) include $path; 
                else echo '<div class="pjm-alert error">Błąd: Brak pliku loop-form.php</div>';
                ?>
            </div>

        </div>

        <div class="pjm-app-sidebar">
            
            <div id="pjm-dynamic-sidebar-area"></div>

            <div class="jm-global-cart sticky-cart" id="pjm-global-cart-section">
                <div class="jm-cart-header">
                    <h4>Twój Koszyk <span id="cart-count" class="badge-count">0</span></h4>
                </div>
                
                <div id="global-items-list" class="jm-cart-items">
                    <div class="pjm-empty-state">
                        <span class="material-symbols-rounded">shopping_cart_off</span>
                        <p>Koszyk jest pusty</p>
                    </div>
                </div>

                <div class="jm-cart-footer">
                    <div class="jm-live-total">
                        <span>Razem netto:</span>
                        <strong id="global-total">0,00 zł</strong>
                    </div>
                    
                    <button type="button" class="jm-btn-checkout disabled" id="btn-global-checkout" onclick="PJM_Core.checkout()">
                        Przejdź do kasy <span class="material-symbols-rounded">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
/* Przełącz zakładkę wg ?service= (np. wejście z publicznego kalkulatora /na-zywo, /petla). */
(function(){
    try {
        var svc = new URLSearchParams(window.location.search).get('service');
        var allowed = ['video','text','event','loop'];
        if (svc && allowed.indexOf(svc) !== -1 && svc !== 'video') {
            window.addEventListener('load', function(){
                var btn = document.querySelector('.pjm-tab-btn[data-tab="'+svc+'"]');
                if (btn) btn.click();
            });
        }
    } catch(e) {}
})();
</script>