<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
    <h1>Konfiguracja Systemu PJM</h1>
    <p>Zarządzaj kluczami API i integracjami zewnętrznymi.</p>

    <form method="post" action="options.php" style="background:#fff; padding:30px; border-radius:8px; border:1px solid #ccd0d4; max-width:900px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <?php settings_fields('pjm_settings_group'); ?>
        
        <h2 style="border-bottom:1px solid #eee; padding-bottom:10px;">
            <span class="dashicons dashicons-text-page"></span> Integracja Fakturownia.pl
        </h2>
        <table class="form-table">
            <tr>
                <th scope="row">Adres</th>
                <td>
                    <input type="text" name="pjm_fakturownia_host" value="<?php echo esc_attr(get_option('pjm_fakturownia_host')); ?>" class="regular-text" placeholder="np. twojafirma.fakturownia.pl" />
                    <p class="description">Pełna domena Twojego konta.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">API Token</th>
                <td>
                    <input type="password" name="pjm_fakturownia_token" value="<?php echo esc_attr(get_option('pjm_fakturownia_token')); ?>" class="regular-text" />
                </td>
            </tr>
        </table>

        <br><br>

        <h2 style="border-bottom:1px solid #eee; padding-bottom:10px;">
            <span class="dashicons dashicons-cart"></span> Płatności Stripe
        </h2>
        <table class="form-table">
            <tr>
                <th scope="row">Publishable Key</th>
                <td>
                    <input type="text" name="pjm_stripe_publishable_key" value="<?php echo esc_attr(get_option('pjm_stripe_publishable_key')); ?>" class="regular-text" placeholder="pk_live_..." />
                </td>
            </tr>
            <tr>
                <th scope="row">Secret Key</th>
                <td>
                    <input type="password" name="pjm_stripe_secret_key" value="<?php echo esc_attr(get_option('pjm_stripe_secret_key')); ?>" class="regular-text" placeholder="sk_live_..." />
                </td>
            </tr>
            <tr>
                <th scope="row">Webhook Signing Secret</th>
                <td>
                    <input type="password" name="pjm_stripe_webhook_secret" value="<?php echo esc_attr(get_option('pjm_stripe_webhook_secret')); ?>" class="regular-text" placeholder="whsec_..." />
                    <p class="description">
                        Wymagany do automatycznych odnowień subskrypcji.<br>
                        URL Webhooka do wpisania w Stripe: <code><?php echo site_url('/wp-json/pjm/v1/stripe-webhook'); ?></code>
                    </p>
                </td>
            </tr>
        </table>

        <br><br>

        <h2 style="border-bottom:1px solid #eee; padding-bottom:10px;">
            <span class="dashicons dashicons-calendar-alt"></span> Google Calendar
        </h2>
        <?php
            $gcal_connected = class_exists('PJM_Google_Calendar') && (new PJM_Google_Calendar())->is_connected();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">Client ID</th>
                <td>
                    <input type="text" name="pjm_gcal_client_id" value="<?php echo esc_attr(get_option('pjm_gcal_client_id')); ?>" class="regular-text" placeholder="xxxxxx.apps.googleusercontent.com" />
                    <p class="description">Z <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a> &rarr; Credentials &rarr; OAuth 2.0 Client ID.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Client Secret</th>
                <td>
                    <input type="password" name="pjm_gcal_client_secret" value="<?php echo esc_attr(get_option('pjm_gcal_client_secret')); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">Calendar ID</th>
                <td>
                    <input type="text" name="pjm_gcal_calendar_id" value="<?php echo esc_attr(get_option('pjm_gcal_calendar_id', 'primary')); ?>" class="regular-text" placeholder="primary" />
                    <p class="description">ID kalendarza Google (np. <code>primary</code> lub ID kalendarza z ustawie&#324; Google Calendar).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Redirect URI</th>
                <td>
                    <code><?php echo esc_html(rest_url('pjm/v1/gcal-callback')); ?></code>
                    <p class="description">Wklej ten URL jako "Authorized redirect URI" w Google Cloud Console.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Status</th>
                <td>
                    <?php if ($gcal_connected): ?>
                        <span style="color:#2e7d32; font-weight:600;">&#10003; Po&#322;&#261;czono z Google Calendar</span>
                    <?php else: ?>
                        <span style="color:#999;">Nie po&#322;&#261;czono</span>
                        <?php if (get_option('pjm_gcal_client_id')): ?>
                            &mdash;
                            <a href="<?php echo esc_url(rest_url('pjm/v1/gcal-authorize')); ?>" class="button button-primary" style="margin-left:8px;">Autoryzuj Google Calendar</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <br><br>

        <h2 style="border-bottom:1px solid #eee; padding-bottom:10px;">
            <span class="dashicons dashicons-translation"></span> Kody językowe
        </h2>
        <?php
            $lang_codes = get_option( 'pjm_language_codes', [] );
            if ( ! is_array( $lang_codes ) || empty( $lang_codes ) ) {
                $lang_codes = class_exists( 'PJM_DB_Manager' ) ? PJM_DB_Manager::default_language_codes() : [];
            }
            $lang_text = '';
            foreach ( $lang_codes as $code => $info ) {
                $lang_text .= $code . ' | ' . ( $info['label'] ?? '' ) . ' | ' . ( $info['type'] ?? 'spoken' ) . "\n";
            }
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">Słownik kodów</th>
                <td>
                    <textarea name="pjm_language_codes" rows="10" class="large-text code" style="font-family:monospace;"><?php echo esc_textarea( trim( $lang_text ) ); ?></textarea>
                    <p class="description">
                        Jedna linia = jeden kod, format: <code>KOD | Nazwa | typ</code> (typ: <code>sign</code> lub <code>spoken</code>).<br>
                        Np. <code>PJM | Polski J&#281;zyk Migowy | sign</code> &nbsp; lub &nbsp; <code>EN | angielski | spoken</code>.
                        U&#380;ywane przy przypisywaniu t&#322;umaczy do zlece&#324;. Dodanie kodu nie wymaga migracji bazy.
                    </p>
                </td>
            </tr>
        </table>

        <br><br>

        <h2 style="border-bottom:1px solid #eee; padding-bottom:10px;">
            <span class="dashicons dashicons-admin-network"></span> Inne ustawienia
        </h2>
        <table class="form-table">
            <tr>
                <th scope="row">API Secret Key (PJM)</th>
                <td>
                    <input type="text" name="pjm_api_secret_key" value="<?php echo esc_attr(get_option('pjm_api_secret_key')); ?>" class="regular-text" />
                    <p class="description">Klucz zabezpieczający wewnętrzne API wtyczki.</p>
                </td>
            </tr>
        </table>

        <?php submit_button('Zapisz konfigurację', 'primary large'); ?>
    </form>
</div>