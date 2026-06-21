<?php
/**
 * Deinstalacja wtyczki PJM.
 * Usuwa dane TYLKO gdy włączona flaga 'pjm_delete_data_on_uninstall'.
 * Domyślnie dane (zamówienia, klienci, faktury) są ZACHOWYWANE.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

if ( ! get_option( 'pjm_delete_data_on_uninstall' ) ) {
    return; // świadoma decyzja: bez flagi nic nie kasujemy
}

global $wpdb;

// 1. Tabele
$tables = [
    'pjm_orders', 'pjm_user_settings', 'pjm_subscriptions', 'pjm_wallet_transactions',
    'pjm_order_messages', 'pjm_system_coupons', 'pjm_order_logs',
    'pjm_translators', 'pjm_order_assignments', 'pjm_settlements',
    'pjm_products', 'pjm_order_items', 'pjm_downloads', 'pjm_api_keys',
    'pjm_portfolio', // tworzona osobno w module Portfolio
];
foreach ( $tables as $t ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$t}" );
}

// 2. Opcje
$options = [
    'pjm_db_version', 'pjm_rates', 'pjm_api_secret_key',
    'pjm_fakturownia_host', 'pjm_fakturownia_token',
    'pjm_stripe_publishable_key', 'pjm_stripe_secret_key', 'pjm_stripe_webhook_secret',
    'pjm_gcal_client_id', 'pjm_gcal_client_secret', 'pjm_gcal_calendar_id', 'pjm_gcal_tokens',
    'pjm_language_codes', 'pjm_gesture_rate', 'pjm_delete_data_on_uninstall',
];
foreach ( $options as $o ) {
    delete_option( $o );
}

// 3. User meta
$meta_keys = [ 'pjm_wallet_units', 'pjm_overage_discount', 'pjm_active_plan', 'pjm_contract_end',
    'pjm_user_discount', 'pjm_individual_discount', 'pjm_loop_free_tokens', '_pjm_reset_code', '_pjm_reset_code_time' ];
foreach ( $meta_keys as $mk ) {
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s", $mk ) );
}

// 4. Cron
$ts = wp_next_scheduled( 'pjm_daily_maintenance' );
if ( $ts ) wp_unschedule_event( $ts, 'pjm_daily_maintenance' );
