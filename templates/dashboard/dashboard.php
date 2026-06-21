<?php if ( ! defined( 'ABSPATH' ) ) exit; 

// --- 1. DANE GLOBALNE ---
global $wpdb;
$user_id = get_current_user_id();
$user_info = get_userdata($user_id);
$table_subs = $wpdb->prefix . 'pjm_subscriptions';

$active_plan_name = 'Brak pakietu';
$plan_class = 'plan-none';

if( $wpdb->get_var("SHOW TABLES LIKE '$table_subs'") == $table_subs ) {
    $sub = $wpdb->get_row( $wpdb->prepare(
        "SELECT plan_id, ends_at FROM $table_subs WHERE user_id = %d AND status = 'active' AND ends_at > NOW() LIMIT 1",
        $user_id
    ));
    if($sub) {
        $names = ['basic'=>'Podstawowy', 'standard'=>'Standard', 'plus'=>'Plus', 'pro'=>'Pro', 'premium'=>'Premium'];
        $active_plan_name = $names[$sub->plan_id] ?? ucfirst($sub->plan_id);
        $plan_class = 'plan-active';
    }
}
?>

<div class="pjm-dashboard-wrapper">
    
    <div id="pjm-overlay" class="pjm-overlay"></div>

    <div class="pjm-mobile-header">
        <button id="pjm-toggle-sidebar" class="pjm-hamburger">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="pjm-brand-mobile">
            <span class="material-symbols-rounded logo-icon">interpreter_mode</span>
            <span class="brand-name">Panel PJM</span>
        </div>
        <div class="pjm-mobile-avatar">
            <?php echo get_avatar( $user_id, 32 ); ?>
        </div>
    </div>
    
    <aside id="pjm-sidebar" class="pjm-sidebar">
        <button id="pjm-close-sidebar" class="pjm-close-btn">
            <span class="material-symbols-rounded">close</span>
        </button>

        <div class="pjm-brand">
            <span class="material-symbols-rounded logo-icon">interpreter_mode</span>
            <span class="brand-name">Panel PJM</span>
        </div>

        <nav class="pjm-nav">
            <ul>
                <?php foreach ( $tabs as $slug => $info ): 
                    if ( ! empty($info['hidden']) && $info['hidden'] === true ) continue;
                    $is_active = ( $active_tab === $slug ) ? 'active' : '';
                ?>
                <li>
                    <a href="?tab=<?php echo esc_attr( $slug ); ?>" class="<?php echo $is_active; ?>">
                        <span class="material-symbols-rounded"><?php echo esc_html( $info['icon'] ); ?></span>
                        <span class="link-text"><?php echo esc_html( $info['label'] ); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="nav-spacer"></div>

            <ul class="nav-bottom">
                <li class="logout-item">
                    <a href="<?php echo wp_logout_url( home_url() ); ?>">
                        <span class="material-symbols-rounded">logout</span>
                        <span class="link-text">Wyloguj się</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="pjm-content">
        
        <header class="pjm-topbar">
            <div class="pjm-welcome">
                <h2><?php echo isset($data['tabs'][$data['active_tab']]) ? esc_html($data['tabs'][$data['active_tab']]['label']) : 'Panel Klienta'; ?></h2>
                <span class="subtitle">Witaj, <?php echo esc_html( $user_info->first_name ?: $user_info->display_name ); ?></span>
            </div>
            
            <div class="pjm-user-profile">
                <div class="user-plan-badge <?php echo $plan_class; ?>">
                    <span class="material-symbols-rounded">verified</span>
                    <div class="plan-details">
                        <span class="plan-label">Twój pakiet</span>
                        <span class="plan-name"><?php echo esc_html($active_plan_name); ?></span>
                    </div>
                </div>

                <div class="user-divider"></div>

                <div class="user-info-block">
                    <div class="text-right">
                        <span class="u-name"><?php echo esc_html( $user_info->first_name ?: $user_info->display_name ); ?></span>
                        <?php
                        // Rola w rogu — realna, nie zawsze „Klient".
                        $pjm_role_label = 'Klient';
                        if ( user_can( $user_id, 'manage_options' ) ) {
                            $pjm_role_label = 'Administrator';
                        } elseif ( function_exists( 'pjm_get_translator_by_user' ) && pjm_get_translator_by_user( $user_id ) ) {
                            $pjm_role_label = 'Tłumacz';
                        }
                        ?>
                        <span class="u-role"><?php echo esc_html( $pjm_role_label ); ?></span>
                    </div>
                    <div class="pjm-avatar">
                        <?php echo get_avatar( $user_id, 42 ); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="pjm-tab-content-wrapper">
            <?php 
            $tab = sanitize_key( $data['active_tab'] );
            
            if ( $tab === 'checkout' ) {
                $file_path = PJM_CALC_PATH . 'templates/checkout/global-checkout.php';
            } else {
                $file_path = PJM_CALC_PATH . 'templates/dashboard/my-' . $tab . '.php';
            }
            
            if ( file_exists( $file_path ) ) {
                include $file_path;
            } else {
                echo '<div class="pjm-empty-state">
                        <span class="material-symbols-rounded">construction</span>
                        <h3>Moduł w budowie</h3>
                        <p>Plik <code>my-'.esc_html($tab).'.php</code> nie istnieje.</p>
                      </div>';
            }
            ?>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('pjm-toggle-sidebar');
    const closeBtn = document.getElementById('pjm-close-sidebar');
    const sidebar = document.getElementById('pjm-sidebar');
    const overlay = document.getElementById('pjm-overlay');

    function toggleMenu() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    }

    if(toggleBtn) toggleBtn.addEventListener('click', toggleMenu);
    if(closeBtn) closeBtn.addEventListener('click', toggleMenu);
    if(overlay) overlay.addEventListener('click', toggleMenu);
});
</script>