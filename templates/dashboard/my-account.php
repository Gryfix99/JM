<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$user_id = get_current_user_id();
$user_info = get_userdata( $user_id );
$table_settings = $wpdb->prefix . 'pjm_user_settings';

// --- 1. OBSŁUGA ZAPISU FORMULARZA ---
$message = '';
$msg_type = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['pjm_account_nonce'] ) && wp_verify_nonce( $_POST['pjm_account_nonce'], 'pjm_save_account' ) ) {
    
    // A. Walidacja typów klienta
    $client_type = sanitize_key( $_POST['client_type'] ); // 'private' lub 'company'
    $company_name = sanitize_text_field( $_POST['company_name'] );
    $company_nip  = sanitize_text_field( $_POST['company_nip'] );

    // Walidacja dla firmy
    if ( $client_type === 'company' && ( empty($company_name) || empty($company_nip) ) ) {
        $message = "W przypadku konta firmowego, nazwa firmy i NIP są wymagane.";
        $msg_type = "error";
    } else {
        // B. Aktualizacja Usera WP
        $display_name = sanitize_text_field( $_POST['display_name'] );
        $email        = sanitize_email( $_POST['user_email'] );
        
        if ( email_exists( $email ) && email_exists( $email ) !== $user_id ) {
            $message = "Podany adres e-mail jest już zajęty.";
            $msg_type = "error";
        } else {
            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $display_name,
                'user_email'   => $email
            ]);

            // C. Hasło
            if ( ! empty( $_POST['new_pass'] ) ) {
                if ( $_POST['new_pass'] === $_POST['confirm_pass'] ) {
                    wp_set_password( $_POST['new_pass'], $user_id );
                    $pass_msg = " Hasło zostało zmienione (zaloguj się ponownie).";
                    wp_signon([
                        'user_login'    => $user_info->user_login,
                        'user_password' => $_POST['new_pass'],
                        'remember'      => true
                    ]); 
                } else {
                    $message = "Hasła nie są identyczne.";
                    $msg_type = "error";
                }
            }

            // D. Aktualizacja pjm_user_settings
            $phone = sanitize_text_field( $_POST['phone'] );
            
            // --- NOWY FORMAT ADRESU (ROZBITY) ---
            $address_data = [
                'street'      => sanitize_text_field( $_POST['addr_street'] ),
                'building_no' => sanitize_text_field( $_POST['addr_building_no'] ),
                'flat_no'     => sanitize_text_field( $_POST['addr_flat_no'] ), // Opcjonalne
                'postcode'    => sanitize_text_field( $_POST['addr_postcode'] ),
                'city'        => sanitize_text_field( $_POST['addr_city'] )
            ];
            $address_json = json_encode( $address_data );

            // Sprawdź czy wpis istnieje
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $table_settings WHERE user_id = %d", $user_id ) );

            $data_db = [
                'client_type'  => $client_type,
                'company_name' => ($client_type === 'company') ? $company_name : '',
                'company_nip'  => ($client_type === 'company') ? $company_nip : '',
                'address_json' => $address_json
            ];

            if ( $exists ) {
                $wpdb->update( $table_settings, $data_db, [ 'user_id' => $user_id ] );
            } else {
                $data_db['user_id'] = $user_id;
                $wpdb->insert( $table_settings, $data_db );
            }
            
            update_user_meta( $user_id, 'billing_phone', $phone );

            if ( empty( $message ) ) {
                $message = "Ustawienia zapisane." . ($pass_msg ?? '');
                $msg_type = "success";
                $user_info = get_userdata( $user_id ); // Refresh
            }
        }
    }
}

// --- 2. POBIERANIE DANYCH ---
$settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_settings WHERE user_id = %d", $user_id ) );
$client_type = $settings->client_type ?? 'private'; 
$address = !empty($settings->address_json) ? json_decode($settings->address_json, true) : [];
$phone = get_user_meta( $user_id, 'billing_phone', true );

?>

<div class="pjm-account-container">
    
    <div class="header-flex pjm-mb-20">
        <h3 class="section-title">Ustawienia Konta</h3>
    </div>

    <?php if ( $message ) : ?>
        <div class="pjm-alert <?php echo esc_attr( $msg_type ); ?>">
            <span class="material-symbols-rounded">
                <?php echo $msg_type === 'success' ? 'check_circle' : 'error'; ?>
            </span>
            <?php echo esc_html( $message ); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="pjm-account-form" id="pjmAccountForm">
        <?php wp_nonce_field( 'pjm_save_account', 'pjm_account_nonce' ); ?>
        
        <div class="pjm-grid-2-col">
            
            <div class="pjm-card account-card">
                <div class="card-title">
                    <span class="material-symbols-rounded">person</span> Dane Logowania
                </div>
                
                <div class="form-group">
                    <label>Imię i Nazwisko</label>
                    <input type="text" name="display_name" value="<?php echo esc_attr( $user_info->display_name ); ?>" class="pjm-input" required>
                </div>

                <div class="form-group">
                    <label>Adres E-mail</label>
                    <input type="email" name="user_email" value="<?php echo esc_attr( $user_info->user_email ); ?>" class="pjm-input" required>
                </div>

                <div class="form-group">
                    <label>Numer Telefonu</label>
                    <input type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" class="pjm-input" placeholder="+48 ...">
                </div>

                <div class="divider"></div>
                
                <div class="card-title small">
                    <span class="material-symbols-rounded">lock_reset</span> Zmiana Hasła
                </div>
                <p class="hint-text">Wypełnij poniższe pola tylko, jeśli chcesz zmienić hasło.</p>
                
                <div class="form-group">
                    <label>Nowe Hasło</label>
                    <input type="password" name="new_pass" class="pjm-input" autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label>Powtórz Hasło</label>
                    <input type="password" name="confirm_pass" class="pjm-input" autocomplete="new-password">
                </div>
            </div>

            <div class="pjm-card account-card">
                <div class="card-title">
                    <span class="material-symbols-rounded">receipt_long</span> Dane do Faktury
                </div>

                <div class="account-type-selector">
                    <label class="type-option">
                        <input type="radio" name="client_type" value="private" <?php checked( $client_type, 'private' ); ?>>
                        <div class="option-box">
                            <span class="material-symbols-rounded">person</span>
                            <span>Osoba Prywatna</span>
                        </div>
                    </label>
                    <label class="type-option">
                        <input type="radio" name="client_type" value="company" <?php checked( $client_type, 'company' ); ?>>
                        <div class="option-box">
                            <span class="material-symbols-rounded">apartment</span>
                            <span>Firma / Instytucja</span>
                        </div>
                    </label>
                </div>
                
                <div id="company-fields-wrapper" class="<?php echo $client_type === 'private' ? 'hidden-fields' : ''; ?>">
                    <div class="form-group">
                        <label>Pełna Nazwa Firmy / Instytucji <span class="req">*</span></label>
                        <input type="text" name="company_name" id="company_name" value="<?php echo esc_attr( $settings->company_name ?? '' ); ?>" class="pjm-input">
                    </div>

                    <div class="form-group">
                        <label>NIP <span class="req">*</span></label>
                        <input type="text" name="company_nip" id="company_nip" value="<?php echo esc_attr( $settings->company_nip ?? '' ); ?>" class="pjm-input" placeholder="0000000000">
                    </div>
                </div>

                <div class="address-section-title pjm-mt-20">Adres</div>

                <div class="form-row-flex address-row-street">
                    <div class="form-group col-street">
                        <label>Ulica</label>
                        <input type="text" name="addr_street" value="<?php echo esc_attr( $address['street'] ?? '' ); ?>" class="pjm-input" required>
                    </div>
                    <div class="form-group col-building">
                        <label>Nr domu</label>
                        <input type="text" name="addr_building_no" value="<?php echo esc_attr( $address['building_no'] ?? '' ); ?>" class="pjm-input" required>
                    </div>
                    <div class="form-group col-flat">
                        <label>Nr lokalu</label>
                        <input type="text" name="addr_flat_no" value="<?php echo esc_attr( $address['flat_no'] ?? '' ); ?>" class="pjm-input" placeholder="-">
                    </div>
                </div>

                <div class="form-row-flex address-row-city">
                    <div class="form-group col-postcode">
                        <label>Kod pocztowy</label>
                        <input type="text" name="addr_postcode" value="<?php echo esc_attr( $address['postcode'] ?? '' ); ?>" class="pjm-input" placeholder="00-000" required>
                    </div>
                    <div class="form-group col-city">
                        <label>Miejscowość</label>
                        <input type="text" name="addr_city" value="<?php echo esc_attr( $address['city'] ?? '' ); ?>" class="pjm-input" required>
                    </div>
                </div>

                <div class="info-box-blue">
                    <span class="material-symbols-rounded icon">info</span>
                    <div>
                        <strong>Dane do dokumentów</strong>
                        
                    </div>
                </div>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary pjm-btn-submit save-btn">
                <span class="material-symbols-rounded">save</span> Zapisz Zmiany
            </button>
        </div>
    </form>
</div>

<script src="<?php echo PJM_CALC_URL . 'assets/js/my-account.js'; ?>"></script>