<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Panel klienta — „Moje pobrania" (kupione produkty cyfrowe).
 */
global $wpdb;
$uid = get_current_user_id();

$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT d.*, p.name AS product_name, o.order_number, o.payment_status, o.order_status
     FROM {$wpdb->prefix}pjm_downloads d
     LEFT JOIN {$wpdb->prefix}pjm_products p ON d.product_id = p.id
     LEFT JOIN {$wpdb->prefix}pjm_orders o ON d.order_id = o.id
     WHERE d.user_id = %d
     ORDER BY d.created_at DESC",
    $uid
) );
?>
<div class="pjm-dl">
    <?php if ( empty( $rows ) ) : ?>
        <div class="pjm-dl-empty">
            <span class="material-symbols-rounded">download_done</span>
            <p>Nie masz jeszcze żadnych produktów cyfrowych.</p>
            <a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>" class="btn btn-primary">Przejdź do sklepu</a>
        </div>
    <?php else : ?>
        <div class="pjm-dl-list">
            <?php foreach ( $rows as $r ) :
                $paid = $r->payment_status === 'paid' || in_array( $r->order_status, [ 'processing', 'completed', 'paid' ], true );
                $expired = ! empty( $r->expires_at ) && strtotime( $r->expires_at . ' UTC' ) < time();
                $used_up = (int) $r->downloads_used >= (int) $r->max_downloads;
                $url = function_exists( 'pjm_download_url' ) ? pjm_download_url( $r->token ) : '#';
            ?>
                <div class="pjm-dl-row">
                    <div class="pjm-dl-info">
                        <strong><?php echo esc_html( $r->product_name ?: 'Produkt' ); ?></strong>
                        <span class="pjm-dl-meta">Zamówienie #<?php echo esc_html( $r->order_number ); ?> &bull;
                            Pobrania: <?php echo (int) $r->downloads_used; ?>/<?php echo (int) $r->max_downloads; ?>
                            <?php if ( $r->expires_at ) : ?>&bull; ważne do <?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $r->expires_at . ' UTC' ) ) ); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="pjm-dl-action">
                        <?php if ( ! $paid ) : ?>
                            <span class="pjm-dl-badge wait">Oczekuje na płatność</span>
                        <?php elseif ( $expired ) : ?>
                            <span class="pjm-dl-badge off">Link wygasł</span>
                        <?php elseif ( $used_up ) : ?>
                            <span class="pjm-dl-badge off">Limit pobrań</span>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $url ); ?>" class="pjm-dl-btn"><span class="material-symbols-rounded">download</span> Pobierz</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="pjm-dl-note">Linki są osobiste i mają ograniczony czas oraz liczbę pobrań. Pobrane pliki zapisz na swoim dysku.</p>
    <?php endif; ?>
</div>

<style>
.pjm-dl { font-family:'Inter',system-ui,sans-serif; }
.pjm-dl-empty { text-align:center; padding:50px 20px; color:#8A938D; }
.pjm-dl-empty .material-symbols-rounded { font-size:48px; color:#cfd8d2; display:block; margin-bottom:10px; }
.pjm-dl-empty .btn { margin-top:14px; }
.pjm-dl-list { display:flex; flex-direction:column; gap:10px; }
.pjm-dl-row { display:flex; justify-content:space-between; align-items:center; gap:14px; background:#fff; border:1px solid #E3E1D9; border-radius:12px; padding:16px 18px; }
.pjm-dl-info strong { display:block; color:#16241D; font-family:'Plus Jakarta Sans',sans-serif; font-size:15px; }
.pjm-dl-meta { font-size:12px; color:#8A938D; }
.pjm-dl-btn { display:inline-flex; align-items:center; gap:6px; background:#1B5E4B; color:#fff; text-decoration:none; padding:9px 18px; border-radius:50px; font-weight:600; font-size:14px; }
.pjm-dl-btn:hover { background:#154A3B; }
.pjm-dl-btn .material-symbols-rounded { font-size:18px; }
.pjm-dl-badge { font-size:12px; font-weight:600; padding:6px 12px; border-radius:50px; }
.pjm-dl-badge.wait { background:#fef3c7; color:#b45309; }
.pjm-dl-badge.off { background:#fee2e2; color:#b91c1c; }
.pjm-dl-note { font-size:12px; color:#8A938D; margin-top:16px; }
@media(max-width:560px){ .pjm-dl-row{ flex-direction:column; align-items:flex-start; } }
</style>
