<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. ZABEZPIECZENIE: Tylko Admin
if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_shop_orders' ) ) {
    echo '<div class="jm-alert error">Brak uprawnień.</div>';
    return;
}

global $wpdb;

// 2. DANE GLOBALNE (KPI - Statystyki widoczne zawsze)
$stats = [
    // Przychód: liczymy zamówienia OPŁACONE (payment_status/is_paid), nie wąsko order_status='paid'
    'income_month' => $wpdb->get_var("SELECT SUM(total_price) FROM {$wpdb->prefix}pjm_orders WHERE ( payment_status = 'paid' OR is_paid = 1 ) AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())"),
    'pending'      => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pjm_orders WHERE order_status IN ('new','processing','pending_payment','payment_pending')"),
    'users'        => count_users()['total_users'],
    'active_subs'  => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pjm_subscriptions WHERE status = 'active'")
];

// Ścieżka do podplików (dostosuj, jeśli masz inną strukturę folderów)
$tabs_path = PJM_CALC_PATH . 'templates/dashboard/admin/';
?>

<div class="ja-dashboard-wrapper">
    
    <div class="ja-header">
        <div class="ja-title">
            <h1>Panel Administratora</h1>
            
        </div>
        <div class="ja-actions">
            <button onclick="location.reload()" class="ja-btn icon-only" title="Odśwież dane">
                <span class="material-symbols-rounded">refresh</span>
            </button>
            <a href="<?php echo admin_url(); ?>" class="ja-btn outline">WP Admin</a>
        </div>
    </div>

    <div class="ja-kpi-grid">
        <div class="ja-card kpi">
            <div class="icon-box blue"><span class="material-symbols-rounded">payments</span></div>
            <div class="kpi-content">
                <span class="label">Przychód (Msc)</span>
                <span class="value"><?php echo number_format((float)$stats['income_month'], 2, ',', ' '); ?> zł</span>
            </div>
        </div>
        <div class="ja-card kpi">
            <div class="icon-box orange"><span class="material-symbols-rounded">hourglass_top</span></div>
            <div class="kpi-content">
                <span class="label">Do realizacji</span>
                <span class="value"><?php echo $stats['pending']; ?></span>
            </div>
        </div>
        <div class="ja-card kpi">
            <div class="icon-box green"><span class="material-symbols-rounded">card_membership</span></div>
            <div class="kpi-content">
                <span class="label">Subskrypcje</span>
                <span class="value"><?php echo $stats['active_subs']; ?></span>
            </div>
        </div>
        <div class="ja-card kpi">
            <div class="icon-box purple"><span class="material-symbols-rounded">group</span></div>
            <div class="kpi-content">
                <span class="label">Klienci</span>
                <span class="value"><?php echo $stats['users']; ?></span>
            </div>
        </div>
    </div>

    <div class="ja-tabs">
        <button class="ja-tab-btn active" data-target="#view-orders">
            <span class="material-symbols-rounded">shopping_bag</span> Zamówienia
        </button>
        <button class="ja-tab-btn" data-target="#view-cal">
            <span class="material-symbols-rounded">calendar_month</span> Kalendarz
        </button>
        <button class="ja-tab-btn" data-target="#view-users">
            <span class="material-symbols-rounded">person_search</span> Użytkownicy
        </button>
        <button class="ja-tab-btn" data-target="#view-subs">
            <span class="material-symbols-rounded">loyalty</span> Abonamenty
        </button>
        <button class="ja-tab-btn" data-target="#view-loops">
            <span class="material-symbols-rounded">hearing</span> Pętle
        </button>
        <button class="ja-tab-btn" data-target="#view-new-order">
            <span class="material-symbols-rounded">post_add</span> Nowe zamówienie
        </button>
        <button class="ja-tab-btn" data-target="#view-translators">
            <span class="material-symbols-rounded">interpreter_mode</span> Tłumacze
        </button>
        <button class="ja-tab-btn" data-target="#view-settlements">
            <span class="material-symbols-rounded">receipt_long</span> Rozliczenia
        </button>
        <button class="ja-tab-btn" data-target="#view-products">
            <span class="material-symbols-rounded">shopping_cart</span> Produkty
        </button>
        <button class="ja-tab-btn" data-target="#view-coupons">
            <span class="material-symbols-rounded">sell</span> Kupony
        </button>
        <button class="ja-tab-btn" data-target="#view-api">
            <span class="material-symbols-rounded">key</span> Klucze API
        </button>
        <button class="ja-tab-btn" data-target="#view-reports">
            <span class="material-symbols-rounded">monitoring</span> Raporty
        </button>
    </div>

    <div class="ja-content-area">
        
        <div id="view-orders" class="ja-view active">
            <?php 
                if(file_exists($tabs_path . 'tab-orders.php')) include $tabs_path . 'tab-orders.php'; 
                else echo "Brak pliku tab-orders.php";
            ?>
        </div>

        <div id="view-cal" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-orders-calendar.php')) include $tabs_path . 'tab-orders-calendar.php';
                else echo "Brak pliku tab-orders-calendar.php";
            ?>
        </div>

        <div id="view-users" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-users.php')) include $tabs_path . 'tab-users.php';
            ?>
        </div>

        <div id="view-subs" class="ja-view">
            <?php 
                if(file_exists($tabs_path . 'tab-subscriptions.php')) include $tabs_path . 'tab-subscriptions.php';
            ?>
        </div>

        <div id="view-loops" class="ja-view">
            <?php
                // Jedna zakładka „Pętle" = pełny panel (kalendarz zajętości + zarządzanie sprzętem).
                if(file_exists($tabs_path . 'tab-loop-calendar.php')) include $tabs_path . 'tab-loop-calendar.php';
                else echo "Brak pliku tab-loop-calendar.php";
            ?>
        </div>

        <div id="view-new-order" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-new-order.php')) include $tabs_path . 'tab-new-order.php';
            ?>
        </div>

        <div id="view-translators" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-translators.php')) include $tabs_path . 'tab-translators.php';
            ?>
        </div>

        <div id="view-settlements" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-settlements.php')) include $tabs_path . 'tab-settlements.php';
            ?>
        </div>

        <div id="view-products" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-products.php')) include $tabs_path . 'tab-products.php';
            ?>
        </div>

        <div id="view-api" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-api.php')) include $tabs_path . 'tab-api.php';
            ?>
        </div>

        <div id="view-reports" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-reports.php')) include $tabs_path . 'tab-reports.php';
            ?>
        </div>

        <div id="view-coupons" class="ja-view">
            <?php
                if(file_exists($tabs_path . 'tab-coupons.php')) include $tabs_path . 'tab-coupons.php';
            ?>
        </div>

    </div>
</div>

<!-- MODAL SZCZEGÓŁÓW ZAMÓWIENIA (treść wstrzykiwana AJAX-em z order-details.php) -->
<div class="ja-modal" id="ja-order-modal">
    <div class="ja-modal-content">
        <span class="ja-modal-close" title="Zamknij">&times;</span>
        <div id="ja-modal-body"></div>
    </div>
</div>