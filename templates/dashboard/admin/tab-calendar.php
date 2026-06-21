<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$gcal = new PJM_Google_Calendar();
$connected = $gcal->is_connected();

$events = [];
if ( $connected ) {
    $now = ( new DateTime( 'now', new DateTimeZone( 'Europe/Warsaw' ) ) )->format( 'c' );
    $future = ( new DateTime( '+30 days', new DateTimeZone( 'Europe/Warsaw' ) ) )->format( 'c' );
    $events = $gcal->list_events( $now, $future, 30 );
}
?>

<div class="ja-calendar-panel">

    <?php if ( ! $connected ) : ?>
        <div class="ja-cal-notice">
            <span class="material-symbols-rounded" style="font-size:40px; color:#1B5E4B;">cloud_off</span>
            <h3>Google Calendar nie jest połączony</h3>
            <p>Aby synchronizować zamówienia z kalendarzem Google, skonfiguruj dane API w
                <a href="<?php echo admin_url( 'admin.php?page=pjm-settings' ); ?>">Ustawieniach PJM</a>
                (sekcja Google Calendar) i kliknij "Autoryzuj".</p>
        </div>
    <?php else : ?>

        <div class="ja-cal-header">
            <div>
                <h2 style="margin:0; font-size:18px;">
                    <span class="material-symbols-rounded" style="vertical-align:middle; color:#4285f4;">event</span>
                    Nadchodzące wydarzenia (30 dni)
                </h2>
                <p style="margin:4px 0 0; color:var(--ja-text-light); font-size:13px;">
                    Połączono z Google Calendar &bull;
                    <span style="color:#2e7d32;">
                        <span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle;">check_circle</span> Aktywne
                    </span>
                </p>
            </div>
            <div class="ja-cal-actions">
                <button class="ja-btn" id="pjm-gcal-sync-all" title="Synchronizuj wszystkie zamówienia">
                    <span class="material-symbols-rounded">sync</span> Synchronizuj
                </button>
            </div>
        </div>

        <?php if ( empty( $events ) ) : ?>
            <div class="ja-cal-empty">
                <span class="material-symbols-rounded">event_busy</span>
                <p>Brak nadchodzących wydarzeń w kalendarzu.</p>
            </div>
        <?php else : ?>
            <div class="ja-cal-timeline">
                <?php
                $current_date = '';
                foreach ( $events as $ev ) :
                    $start_raw = $ev['start']['dateTime'] ?? $ev['start']['date'] ?? '';
                    $is_all_day = isset( $ev['start']['date'] ) && ! isset( $ev['start']['dateTime'] );

                    $dt = new DateTime( $start_raw, new DateTimeZone( 'Europe/Warsaw' ) );
                    $day_label = $dt->format( 'j M Y' );
                    $day_name  = $dt->format( 'l' );

                    $day_names_pl = [
                        'Monday' => 'Poniedziałek', 'Tuesday' => 'Wtorek', 'Wednesday' => 'Środa',
                        'Thursday' => 'Czwartek', 'Friday' => 'Piątek', 'Saturday' => 'Sobota', 'Sunday' => 'Niedziela',
                    ];
                    $day_name = $day_names_pl[ $day_name ] ?? $day_name;

                    if ( $day_label !== $current_date ) :
                        $current_date = $day_label;
                ?>
                    <div class="ja-cal-day-header">
                        <strong><?php echo esc_html( $day_name ); ?></strong>
                        <span><?php echo esc_html( $day_label ); ?></span>
                    </div>
                <?php endif; ?>

                    <div class="ja-cal-event <?php echo strpos( $ev['summary'] ?? '', '[PJM' ) !== false ? 'pjm-event' : ''; ?>">
                        <div class="ja-cal-event-time">
                            <?php if ( $is_all_day ) : ?>
                                <span class="all-day">Cały dzień</span>
                            <?php else : ?>
                                <span><?php echo $dt->format( 'H:i' ); ?></span>
                                <?php
                                    $end_raw = $ev['end']['dateTime'] ?? '';
                                    if ( $end_raw ) {
                                        $end_dt = new DateTime( $end_raw, new DateTimeZone( 'Europe/Warsaw' ) );
                                        echo '<span class="end-time">– ' . $end_dt->format( 'H:i' ) . '</span>';
                                    }
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="ja-cal-event-info">
                            <strong><?php echo esc_html( $ev['summary'] ?? 'Bez tytułu' ); ?></strong>
                            <?php if ( ! empty( $ev['description'] ) ) : ?>
                                <p><?php echo nl2br( esc_html( mb_strimwidth( $ev['description'], 0, 120, '...' ) ) ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
.ja-calendar-panel { max-width: 900px; }
.ja-cal-notice { text-align: center; padding: 50px 20px; background: #f9fafb; border: 2px dashed var(--ja-border); border-radius: 12px; }
.ja-cal-notice h3 { margin: 12px 0 6px; }
.ja-cal-notice p { color: var(--ja-text-light); font-size: 14px; max-width: 500px; margin: 0 auto; }
.ja-cal-notice a { color: #1B5E4B; }

.ja-cal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--ja-border); }
.ja-cal-actions { display: flex; gap: 8px; }

.ja-cal-empty { text-align: center; padding: 40px; color: var(--ja-text-light); }
.ja-cal-empty .material-symbols-rounded { font-size: 48px; display: block; margin-bottom: 10px; color: #ddd; }

.ja-cal-timeline { display: flex; flex-direction: column; gap: 4px; }
.ja-cal-day-header { display: flex; justify-content: space-between; padding: 10px 0 6px; border-bottom: 1px solid #eee; margin-top: 12px; font-size: 13px; color: var(--ja-text-light); }
.ja-cal-day-header:first-child { margin-top: 0; }
.ja-cal-day-header strong { color: var(--ja-primary); }

.ja-cal-event { display: flex; gap: 14px; padding: 10px 14px; border-radius: 8px; transition: background 0.15s; }
.ja-cal-event:hover { background: #f9fafb; }
.ja-cal-event.pjm-event { border-left: 3px solid #1B5E4B; }

.ja-cal-event-time { min-width: 70px; font-size: 13px; color: var(--ja-text-light); padding-top: 2px; }
.ja-cal-event-time .all-day { font-size: 11px; text-transform: uppercase; color: #999; }
.ja-cal-event-time .end-time { display: block; font-size: 11px; color: #aaa; }

.ja-cal-event-info strong { font-size: 14px; display: block; margin-bottom: 2px; }
.ja-cal-event-info p { margin: 0; font-size: 12px; color: var(--ja-text-light); line-height: 1.4; }
</style>

<script>
jQuery(function($) {
    $('#pjm-gcal-sync-all').on('click', function() {
        var $btn = $(this);
        if (!confirm('Synchronizować wszystkie niesynchronizowane zamówienia z Google Calendar?')) return;

        $btn.prop('disabled', true).find('.material-symbols-rounded').text('sync').addClass('ja-spin');

        $.post(pjm_admin_vars.ajax_url, {
            action: 'pjm_admin_gcal_sync_all',
            nonce: pjm_admin_vars.nonce
        }).done(function(res) {
            if (res.success) {
                alert('Zsynchronizowano ' + res.data.synced + ' z ' + res.data.total + ' zamówień.');
                location.reload();
            } else {
                alert('Błąd: ' + (res.data || 'Nieznany'));
            }
        }).fail(function() {
            alert('Błąd połączenia.');
        }).always(function() {
            $btn.prop('disabled', false).find('.material-symbols-rounded').text('sync').removeClass('ja-spin');
        });
    });
});
</script>
