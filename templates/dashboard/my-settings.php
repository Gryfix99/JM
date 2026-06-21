<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

// Wymagane do obsługi uploadu plików w WP
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

global $wpdb;
$user_id = get_current_user_id();
$user_info = get_userdata( $user_id ); // wcześniej niezdefiniowane => pusty e-mail w powiadomieniach
$table_settings = $wpdb->prefix . 'pjm_user_settings';

$message = '';
$msg_type = '';

// --- 1. OBSŁUGA ZAPISU ---
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['pjm_settings_nonce'] ) && wp_verify_nonce( $_POST['pjm_settings_nonce'], 'pjm_save_settings' ) ) {

    // A. Kolor tła
    $bg_color = sanitize_hex_color( $_POST['default_bg_color'] );

    // B. Obsługa Uploadu PLIKÓW (Logo i Avatar)
    $uploaded_files = [];
    $fields = ['company_logo' => 'default_logo_url', 'user_avatar' => 'avatar_url'];

    foreach ($fields as $input_name => $db_column) {
        if ( ! empty( $_FILES[$input_name]['name'] ) ) {
            $file = $_FILES[$input_name];
            
            // Walidacja typu pliku
            $upload_overrides = array( 'test_form' => false, 'mimes' => array('jpg' => 'image/jpeg', 'png' => 'image/png') );
            
            $movefile = wp_handle_upload( $file, $upload_overrides );

            if ( $movefile && ! isset( $movefile['error'] ) ) {
                $uploaded_files[$db_column] = $movefile['url'];
            } else {
                $message = "Błąd wgrywania pliku: " . $movefile['error'];
                $msg_type = "error";
            }
        }
    }

    // C. Zapis do bazy
    if ( empty( $message ) ) {
        // Przygotuj dane do update/insert
        $data_to_save = [];
        if($bg_color) $data_to_save['default_bg_color'] = $bg_color;
        if(!empty($uploaded_files['default_logo_url'])) $data_to_save['default_logo_url'] = $uploaded_files['default_logo_url'];
        if(!empty($uploaded_files['avatar_url'])) $data_to_save['avatar_url'] = $uploaded_files['avatar_url'];

        if ( ! empty( $data_to_save ) ) {
            // Sprawdź czy rekord istnieje
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $table_settings WHERE user_id = %d", $user_id ) );

            if ( $exists ) {
                $wpdb->update( $table_settings, $data_to_save, [ 'user_id' => $user_id ] );
            } else {
                $data_to_save['user_id'] = $user_id;
                $wpdb->insert( $table_settings, $data_to_save );
            }
            
            $message = "Ustawienia zostały zapisane.";
            $msg_type = "success";
        }
    }
}

// --- 2. POBRANIE DANYCH ---
$settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_settings WHERE user_id = %d", $user_id ) );

$current_bg   = $settings->default_bg_color ?? '#FFFFFF';
$current_logo = $settings->default_logo_url ?? '';
$current_avatar = $settings->avatar_url ?? '';

?>

<div class="pjm-settings-container">
    
    <div class="header-flex pjm-mb-20">
        <h3 class="section-title">Preferencje i Wygląd</h3>
    </div>

    <?php if ( $message ) : ?>
        <div class="pjm-alert <?php echo esc_attr( $msg_type ); ?>">
            <span class="material-symbols-rounded">
                <?php echo $msg_type === 'success' ? 'check_circle' : 'error'; ?>
            </span>
            <?php echo esc_html( $message ); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="pjm-settings-form">
        <?php wp_nonce_field( 'pjm_save_settings', 'pjm_settings_nonce' ); ?>

        <div class="pjm-grid-2-col">
            
            <div class="pjm-card settings-card">
                <div class="card-title">
                    <span class="material-symbols-rounded">palette</span> Wygląd tłumaczeń
                </div>
                <p class="hint-text">Te ustawienia będą domyślnie stosowane do każdego zamówionego materiału wideo.</p>

                <div class="form-group">
                    <label>Domyślne tło nagrania</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="default_bg_color" id="bg_color_input" value="<?php echo esc_attr( $current_bg ); ?>">
                        <input type="text" id="bg_color_hex" value="<?php echo esc_attr( $current_bg ); ?>" class="pjm-input small-input" readonly>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label>Logo firmowe</label>
                    
                    <div class="file-upload-preview <?php echo $current_logo ? 'has-file' : ''; ?>">
                        <div class="preview-area">
                            <?php if($current_logo): ?>
                                <img src="<?php echo esc_url($current_logo); ?>" alt="Logo">
                            <?php else: ?>
                                <span class="material-symbols-rounded icon-placeholder">image</span>
                            <?php endif; ?>
                        </div>
                        <div class="upload-actions">
                            <label for="company_logo" class="btn btn-outline btn-sm">
                                <span class="material-symbols-rounded">upload</span> Zmień plik
                            </label>
                            <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg" style="display:none;" onchange="previewImage(this, '.preview-area')">
                            <span class="file-info">PNG lub JPG, max 2MB</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pjm-card settings-card">
                <div class="card-title">
                    <span class="material-symbols-rounded">account_circle</span> Twój Profil
                </div>
                

                <div class="form-group">
                    <label>Zdjęcie profilowe</label>
                    
                    <div class="avatar-upload-wrapper">
                        <div class="avatar-preview">
                            <?php if($current_avatar): ?>
                                <img src="<?php echo esc_url($current_avatar); ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo get_avatar( $user_id, 80 ); ?>
                            <?php endif; ?>
                        </div>
                        <div class="upload-actions">
                            <label for="user_avatar" class="btn btn-outline btn-sm">Wgraj zdjęcie</label>
                            <input type="file" name="user_avatar" id="user_avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this, '.avatar-preview')">
                        </div>
                    </div>
                </div>

                <div class="info-box-blue pjm-mt-20">
                    <span class="material-symbols-rounded icon">notifications</span>
                    <div>
                        <strong>Powiadomienia</strong>
                        <p>Powiadomienia o zmianie statusu zamówienia są wysyłane automatycznie na Twój adres e-mail: <strong><?php echo esc_html($user_info->user_email); ?></strong>.</p>
                    </div>
                </div>

            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary pjm-btn-submit save-btn">
                <span class="material-symbols-rounded">save</span> Zapisz Ustawienia
            </button>
        </div>

    </form>
</div>

<script src="<?php echo PJM_CALC_URL . 'assets/js/my-settings.js'; ?>"></script>