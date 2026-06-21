<?php
/**
 * Plugin Name: System Zamówień PJM
 * Description: Zaawansowany system wyceny, subskrypcji i obsługi zamówień PJM (Modułowy).
 * Version: 8.16.6
 * Author: Janusz Pierzchalski
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. DEFINICJE ŚCIEŻEK
 */
define( 'PJM_CALC_PATH', plugin_dir_path( __FILE__ ) );
define( 'PJM_CALC_URL',  plugin_dir_url( __FILE__ ) );

// Jedno źródło wersji (cache-busting assetów) — czytane z nagłówka wtyczki.
if ( ! defined( 'PJM_VERSION' ) ) {
    if ( ! function_exists( 'get_file_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $pjm_meta = get_file_data( __FILE__, [ 'version' => 'Version' ] );
    define( 'PJM_VERSION', ! empty( $pjm_meta['version'] ) ? $pjm_meta['version'] : '8.3.0' );
}

/**
 * 2. GLOBALNE ŁADOWANIE STRIPE PHP SDK
 */
if ( ! class_exists( '\Stripe\Stripe' ) ) {
    $stripe_sdk = PJM_CALC_PATH . 'includes/libs/stripe-php/init.php';
    if ( file_exists( $stripe_sdk ) ) {
        require_once $stripe_sdk;
    } else {
        error_log( '❌ PJM Stripe SDK Error: Nie znaleziono biblioteki w ' . $stripe_sdk );
    }
}

/**
 * 3. ŁADOWANIE PLIKÓW (STRUKTURA MODUŁOWA)
 */

// A. CORE
require_once PJM_CALC_PATH . 'includes/core/helpers.php';
require_once PJM_CALC_PATH . 'includes/core/class-db-manager.php';
require_once PJM_CALC_PATH . 'includes/core/class-assets.php';
require_once PJM_CALC_PATH . 'includes/core/class-auth.php';
require_once PJM_CALC_PATH . 'includes/core/class-mailer.php';
require_once PJM_CALC_PATH . 'includes/core/class-shortcode.php';
require_once PJM_CALC_PATH . 'includes/core/class-admin-dashboard.php';
require_once PJM_CALC_PATH . 'includes/core/class-customer-portal.php';
require_once PJM_CALC_PATH . 'includes/core/class-api-handler.php';
require_once PJM_CALC_PATH . 'includes/core/class-user-manager.php';

// B. MODUŁY
require_once PJM_CALC_PATH . 'includes/modules/calculator/class-calculator-handler.php';
require_once PJM_CALC_PATH . 'includes/modules/subscription/class-subscription-handler.php';
require_once PJM_CALC_PATH . 'includes/modules/subscription/class-cron-handler.php';
require_once PJM_CALC_PATH . 'includes/modules/portfolio/class-portfolio-handler.php';

// C. CALENDAR — integracja OAuth z Google Calendar WYŁĄCZONA na życzenie.
// Zachowujemy tylko linki „Dodaj do Google Kalendarza" (zwykłe URL-e render, bez połączenia/synchronizacji).
// require_once PJM_CALC_PATH . 'includes/modules/calendar/class-google-calendar.php';

// C2. TŁUMACZE / ROZLICZENIA (Translator Management)
require_once PJM_CALC_PATH . 'includes/modules/translators/class-translator-handler.php';

// C3. SEO (dane strukturalne, meta, Open Graph)
require_once PJM_CALC_PATH . 'includes/modules/seo/class-seo.php';

// C3b. POWIADOMIENIA (e-mail o zmianie statusu zamówienia)
require_once PJM_CALC_PATH . 'includes/modules/notifications/class-notifications.php';

// C4. E-COMMERCE (produkty cyfrowe — katalog, pobrania, klucze API)
require_once PJM_CALC_PATH . 'includes/modules/ecommerce/class-downloads.php';
if ( file_exists( PJM_CALC_PATH . 'includes/modules/ecommerce/class-products.php' ) ) {
    require_once PJM_CALC_PATH . 'includes/modules/ecommerce/class-products.php';
}
if ( file_exists( PJM_CALC_PATH . 'includes/modules/ecommerce/class-api-keys.php' ) ) {
    require_once PJM_CALC_PATH . 'includes/modules/ecommerce/class-api-keys.php';
}
if ( file_exists( PJM_CALC_PATH . 'includes/modules/ecommerce/class-shop.php' ) ) {
    require_once PJM_CALC_PATH . 'includes/modules/ecommerce/class-shop.php';
}

// D. PAYMENT
require_once PJM_CALC_PATH . 'includes/modules/payment/class-pricing.php';
require_once PJM_CALC_PATH . 'includes/modules/payment/class-fakturownia.php';
require_once PJM_CALC_PATH . 'includes/modules/payment/class-wallet-handler.php';
require_once PJM_CALC_PATH . 'includes/modules/payment/class-stripe-handler.php'; // Musi być przed Orchestratorem
require_once PJM_CALC_PATH . 'includes/modules/payment/class-coupons.php';
require_once PJM_CALC_PATH . 'includes/modules/payment/class-checkout-orchestrator.php';

/**
 * 4. INSTALACJA / DEINSTALACJA
 */
define( 'PJM_DB_VERSION', '8.15.2' ); // bump przy zmianie schematu => auto-migracja

register_activation_hook( __FILE__, [ 'PJM_DB_Manager', 'create_tables' ] );
register_activation_hook( __FILE__, [ 'PJM_Cron_Handler', 'activate_cron' ] );
register_deactivation_hook( __FILE__, [ 'PJM_Cron_Handler', 'deactivate_cron' ] );

/**
 * Auto-migracja schematu na działającej instalacji (bez deaktywacji wtyczki).
 * dbDelta jest idempotentne — bezpieczne przy każdej zmianie wersji.
 */
add_action( 'plugins_loaded', function () {
    if ( get_option( 'pjm_db_version' ) !== PJM_DB_VERSION ) {
        PJM_DB_Manager::create_tables();
        update_option( 'pjm_db_version', PJM_DB_VERSION );
        // Sprzątanie: usuń przestarzałą opcję auto-nagłówka (od teraz steruje TYLKO stała PJM_AUTO_HEADER).
        delete_option( 'pjm_header_auto' );
    }
    // Fail-closed API wymaga klucza — generujemy silny, jeśli pusty.
    // UWAGA: po wygenerowaniu ustaw TEN SAM klucz w integracji DEvents (Ustawienia PJM → API Secret Key).
    if ( ! get_option( 'pjm_api_secret_key' ) ) {
        update_option( 'pjm_api_secret_key', wp_generate_password( 48, false, false ) );
    }
}, 5 );

/**
 * 5. INICJALIZACJA SYSTEMU
 */
add_action( 'plugins_loaded', function() {
    new PJM_DB_Manager();
    new PJM_Calc_Assets();
    new PJM_Mailer();
    new PJM_Auth();
    new PJM_Cron_Handler();
    new PJM_Calc_Shortcode();
    new PJM_Admin_Dashboard();
    new PJM_Customer_Portal();
    new PJM_API_Handler();
    new PJM_Calculator_Handler();
    new PJM_Subscription_Handler();
    new PJM_Stripe_Handler();
    new PJM_Coupon_Handler();
    new PJM_Checkout_Orchestrator();
    // new PJM_Google_Calendar(); // OAuth z Google Calendar wyłączony — zostają tylko linki „dodaj do kalendarza"
    new PJM_Translator_Handler();
    new PJM_SEO();
    new PJM_Notifications();
    new PJM_Downloads();
    if ( class_exists( 'PJM_Products' ) )  new PJM_Products();
    if ( class_exists( 'PJM_Api_Keys' ) )  new PJM_Api_Keys();
    if ( class_exists( 'PJM_Shop' ) )      new PJM_Shop();
});

/**
 * 6. GLOBALNE HELPERSY
 */
add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        show_admin_bar( false );
    }
});

add_action( 'wp_logout', function () {
    wp_redirect( home_url() );
    exit;
});

/**
 * 7. HEADER & FOOTER — jako SHORTCODE [pjm_header] / [pjm_footer] + auto-wstrzykiwanie (domyślnie).
 * Strażnik jednokrotności: render zwraca '' jeśli już wyrenderowano w tym żądaniu (brak duplikatów,
 * gdy używasz shortcode'a i auto-injekcji jednocześnie).
 * Aby wstawiać ręcznie (np. w Elementorze), wyłącz auto: define('PJM_NO_AUTO_HEADER', true)
 * w wp-config.php LUB ustaw opcję pjm_header_auto = '0'.
 */
function pjm_render_header() {
    static $done = false;
    if ( $done ) return '';
    $done = true;
    $tpl = PJM_CALC_PATH . 'templates/components/header.php';
    if ( ! file_exists( $tpl ) ) return '';
    ob_start();
    include $tpl;
    return ob_get_clean();
}
function pjm_render_footer() {
    static $done = false;
    if ( $done ) return '';
    $done = true;
    $tpl = PJM_CALC_PATH . 'templates/components/footer.php';
    if ( ! file_exists( $tpl ) ) return '';
    ob_start();
    include $tpl;
    return ob_get_clean();
}
add_shortcode( 'pjm_header', 'pjm_render_header' );
add_shortcode( 'pjm_footer', 'pjm_render_footer' );

function pjm_header_auto_enabled() {
    // Auto-injekcja nagłówka/stopki jest DOMYŚLNIE WYŁĄCZONA i CELOWO ignoruje opcję w bazie
    // (stare instalacje mogły mieć zapisane pjm_header_auto='1' — to już NIE wstrzyknie nagłówka).
    // Motyw/Elementor ma własny nagłówek. [pjm_header]/[pjm_footer] zostają do RĘCZNEGO wstawienia.
    // Aby (świadomie) włączyć auto-injekcję, dodaj w wp-config.php: define('PJM_AUTO_HEADER', true);
    if ( defined( 'PJM_NO_AUTO_HEADER' ) && PJM_NO_AUTO_HEADER ) return false;
    return defined( 'PJM_AUTO_HEADER' ) && PJM_AUTO_HEADER;
}

add_action( 'wp_body_open', function () {
    if ( is_admin() || ! pjm_header_auto_enabled() ) return;
    echo pjm_render_header();
}, 1 );

add_action( 'wp_footer', function () {
    if ( is_admin() || ! pjm_header_auto_enabled() ) return;
    echo pjm_render_footer();
}, 50 );

// Widżet dostępności (kontrast / rozmiar tekstu) + własny banner cookie — zawsze na froncie.
add_action( 'wp_footer', function () {
    if ( is_admin() ) return;
    $tpl = PJM_CALC_PATH . 'templates/components/a11y-cookie.php';
    if ( file_exists( $tpl ) ) include $tpl;
}, 60 );

/**
 * 7b. WYŁĄCZENIE CSS ELEMENTORA NA PANELU ADMINA (/moje-konto/?tab=admin),
 * bo style Elementora nadpisywały wygląd panelu. Dotyczy tylko zakładki admin.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_page( 'moje-konto' ) ) return;
    if ( ( $_GET['tab'] ?? '' ) !== 'admin' ) return;
    global $wp_styles;
    if ( ! ( $wp_styles instanceof WP_Styles ) ) return;
    foreach ( (array) $wp_styles->queue as $handle ) {
        if ( strpos( $handle, 'elementor' ) !== false
          || strpos( $handle, 'widget-' ) === 0
          || strpos( $handle, 'e-' ) === 0 ) {
            wp_dequeue_style( $handle );
        }
    }
}, 9999 );

/**
 * 7c. ROLE: klient / interpreter (tłumacz) / admin (administrator).
 * Klienci dostają rolę „klient", powiązani tłumacze „interpreter".
 */
function pjm_register_roles() {
    if ( ! get_role( 'klient' ) )      add_role( 'klient', 'Klient', [ 'read' => true ] );
    if ( ! get_role( 'interpreter' ) ) add_role( 'interpreter', 'Tłumacz (interpreter)', [ 'read' => true ] );
}
register_activation_hook( __FILE__, 'pjm_register_roles' );
add_action( 'init', function () {
    if ( get_option( 'pjm_roles_v3' ) !== '1' ) {
        pjm_register_roles();
        update_option( 'pjm_roles_v3', '1' );
    }
} );

/**
 * 7d. WŁASNY FORMULARZ KONTAKTOWY (zastępuje Contact Form 7 + reCAPTCHA).
 * Anty-spam: nonce + honeypot + rate-limit. Mail trafia na admin_email, Reply-To = nadawca.
 */
function pjm_handle_contact_submit() {
    if ( ! check_ajax_referer( 'pjm_contact', 'nonce', false ) ) {
        wp_send_json_error( 'Sesja wygasła — odśwież stronę.' );
    }
    if ( ! empty( $_POST['pjm_company'] ) ) { // honeypot — bot
        wp_send_json_success( [ 'message' => 'Dziękujemy! Wiadomość wysłana.' ] );
    }
    if ( function_exists( 'pjm_rate_limit_ok' ) && ! pjm_rate_limit_ok( 'contact', 5, 600 ) ) {
        wp_send_json_error( 'Zbyt wiele wiadomości. Spróbuj za chwilę.' );
    }
    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $phone   = sanitize_text_field( $_POST['phone'] ?? '' );
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );
    if ( $name === '' || ! is_email( $email ) || $message === '' ) {
        wp_send_json_error( 'Uzupełnij imię, poprawny e-mail i treść wiadomości.' );
    }

    $to    = get_option( 'admin_email' );
    $body  = '<p><strong>Nowa wiadomość z formularza kontaktowego.</strong></p>';
    $body .= '<table style="font-size:14px; border-collapse:collapse;">';
    $body .= '<tr><td style="padding:4px 10px; color:#666;">Imię:</td><td>' . esc_html( $name ) . '</td></tr>';
    $body .= '<tr><td style="padding:4px 10px; color:#666;">E-mail:</td><td>' . esc_html( $email ) . '</td></tr>';
    if ( $phone )   $body .= '<tr><td style="padding:4px 10px; color:#666;">Telefon:</td><td>' . esc_html( $phone ) . '</td></tr>';
    if ( $subject ) $body .= '<tr><td style="padding:4px 10px; color:#666;">Temat:</td><td>' . esc_html( $subject ) . '</td></tr>';
    $body .= '</table><p style="margin-top:12px; white-space:pre-wrap;">' . nl2br( esc_html( $message ) ) . '</p>';

    // Reply-To na adres nadawcy (żeby dało się odpisać bezpośrednio).
    $inject = function ( $args ) use ( $email ) {
        if ( empty( $args['headers'] ) ) $args['headers'] = [];
        if ( is_string( $args['headers'] ) ) $args['headers'] = [ $args['headers'] ];
        $args['headers'][] = 'Reply-To: ' . $email;
        return $args;
    };
    add_filter( 'wp_mail', $inject );

    $sent = false;
    if ( class_exists( 'PJM_Mailer' ) ) {
        $mailer = new PJM_Mailer();
        $sent = $mailer->send_email( $to, 'Kontakt: ' . ( $subject ?: $name ), $body );
    } else {
        add_filter( 'wp_mail_content_type', function () { return 'text/html'; } );
        $sent = wp_mail( $to, 'Kontakt: ' . ( $subject ?: $name ), $body );
    }
    remove_filter( 'wp_mail', $inject );

    // Auto-potwierdzenie do nadawcy — żeby miał ślad, że wiadomość dotarła (klient persona).
    if ( $sent && class_exists( 'PJM_Mailer' ) ) {
        $ack  = '<p>Dzień dobry ' . esc_html( $name ) . ',</p>';
        $ack .= '<p>Dziękujemy za wiadomość — otrzymaliśmy ją i odpowiemy najszybciej, jak to możliwe (zwykle w godzinach pracy pon.–pt. 9–17).</p>';
        if ( $subject ) $ack .= '<p style="color:#666;">Temat: ' . esc_html( $subject ) . '</p>';
        $ack .= '<p style="color:#666; white-space:pre-wrap;">' . nl2br( esc_html( $message ) ) . '</p>';
        $ack .= '<p>Pozdrawiamy,<br>Janusz Migowego</p>';
        ( new PJM_Mailer() )->send_email( $email, 'Otrzymaliśmy Twoją wiadomość', $ack );
    }

    if ( $sent ) wp_send_json_success( [ 'message' => 'Dziękujemy! Odpowiemy najszybciej, jak to możliwe.' ] );
    wp_send_json_error( 'Nie udało się wysłać wiadomości. Napisz bezpośrednio na nasz e-mail.' );
}
add_action( 'wp_ajax_pjm_contact_submit', 'pjm_handle_contact_submit' );
add_action( 'wp_ajax_nopriv_pjm_contact_submit', 'pjm_handle_contact_submit' );

/**
 * 7e. PODGLĄD ZAMÓWIENIA Z LINKU W MAILU (bez logowania).
 * Tokenowany URL: /?pjm_order=ID&t=TOKEN → read-only strona ze statusem, pozycjami,
 * miejscem/linkiem, przydzielonym tłumaczem (imię + rola, BEZ danych kontaktowych) i fakturą.
 */
function pjm_order_preview_url( $order ) {
    global $wpdb;
    if ( is_numeric( $order ) ) {
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT id, preview_token FROM {$wpdb->prefix}pjm_orders WHERE id = %d", (int) $order ) );
    }
    if ( ! $order || empty( $order->id ) ) return '';
    $token = $order->preview_token ?? '';
    if ( empty( $token ) ) {
        $token = wp_generate_password( 32, false );
        $wpdb->update( $wpdb->prefix . 'pjm_orders', [ 'preview_token' => $token ], [ 'id' => $order->id ] );
    }
    return add_query_arg( [ 'pjm_order' => $order->id, 't' => $token ], home_url( '/' ) );
}

add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['pjm_order'] ) ) return;
    global $wpdb;
    $oid   = intval( $_GET['pjm_order'] );
    $token = sanitize_text_field( $_GET['t'] ?? '' );
    $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pjm_orders WHERE id = %d", $oid ) );
    if ( ! $order || empty( $order->preview_token ) || ! hash_equals( (string) $order->preview_token, $token ) ) {
        status_header( 403 );
        nocache_headers();
        wp_die( 'Link do podglądu jest nieprawidłowy lub wygasł. Zaloguj się do panelu, aby zobaczyć zamówienie.', 'Podgląd zamówienia', [ 'response' => 403 ] );
    }
    nocache_headers();
    $tpl = PJM_CALC_PATH . 'templates/public/order-preview.php';
    if ( file_exists( $tpl ) ) { include $tpl; exit; }
    wp_die( 'Brak szablonu podglądu.', 'Podgląd zamówienia', [ 'response' => 500 ] );
} );

/**
 * 8. REJESTRACJA WEBHOOKA (NAPRAWA BŁĘDU 404)
 * Zarejestrowane bezpośrednio, aby WP zawsze widział trasę przed startem innych procesów.
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'pjm/v1', '/stripe-webhook', [
        'methods'             => 'POST, GET', // GET zostawiamy tylko do weryfikacji w przeglądarce
        'callback'            => function ( $data ) {
            
            // Logika dla testu w przeglądarce
            if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
                return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
            }

            // Logika dla Stripe (POST)
            if ( ! class_exists( 'PJM_Stripe_Handler' ) ) {
                return new WP_REST_Response( [ 'message' => 'Błąd: Brak klasy Stripe Handler.' ], 500 );
            }

            $handler = new PJM_Stripe_Handler();
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            
            $result = $handler->handle_webhook_request( $data->get_body(), $sig_header );

            if ( is_wp_error( $result ) ) {
                // Szczegóły tylko do logu serwera; klient dostaje generyczny komunikat (nie ułatwiamy rekonesansu).
                error_log('PJM Webhook Error: ' . $result->get_error_message());
                return new WP_REST_Response( [ 'message' => 'Invalid signature' ], 400 );
            }

            return new WP_REST_Response( [ 'status' => 'success' ], 200 );
        },
        'permission_callback' => '__return_true', 
    ]);
});

add_action( 'plugins_loaded', function() {
    new PJM_Portfolio_System();
}, 20 );

/**
 * 9. SERWEROWY ENDPOINT WYCENY (pjm_quote) — additive.
 * Jedno źródło prawdy o cenie: JS może zweryfikować/odczytać cenę z serwera (PJM_Pricing),
 * zamiast liczyć ją wyłącznie po stronie klienta. Nie zmienia istniejącego przepływu.
 */
function pjm_ajax_quote() {
    check_ajax_referer( 'pjm_calc_nonce', 'nonce' );
    $raw  = isset( $_POST['item'] ) ? wp_unslash( $_POST['item'] ) : '';
    $item = json_decode( $raw, true );
    if ( ! is_array( $item ) ) {
        wp_send_json_error( 'Brak danych pozycji.' );
    }
    if ( ! class_exists( 'PJM_Pricing' ) ) {
        wp_send_json_error( 'Silnik wyceny niedostępny.' );
    }
    $pricing = new PJM_Pricing();
    $total   = $pricing->calculate_service_cost( $item );
    wp_send_json_success( [ 'total' => round( (float) $total, 2 ) ] );
}
add_action( 'wp_ajax_pjm_quote', 'pjm_ajax_quote' );
add_action( 'wp_ajax_nopriv_pjm_quote', 'pjm_ajax_quote' );