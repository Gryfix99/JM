<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 

global $wp;
$current_url = home_url( add_query_arg( array(), $wp->request ) );
$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : site_url('/moje-konto/');

// Generowanie Nonce dla bezpieczeństwa
$auth_nonce = wp_create_nonce( 'pjm_auth_nonce' );
?>

<div class="pjm-auth-container">
    <div class="pjm-auth-card">
        
        <div id="view-login" class="pjm-auth-view active">        
            <form id="pjm-login-form" class="pjm-form">
                <input type="hidden" name="nonce" value="<?php echo $auth_nonce; ?>">
                <input type="hidden" name="action" value="pjm_login_user"> 
                <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
                
                <div class="auth-header">
                    <h1>Witaj ponownie</h1>
                    <p>Zaloguj się, aby zarządzać usługami.</p>
                </div>

                <div class="pjm-input-group icon-group">
                    <span class="material-symbols-rounded icon">person</span>
                    <input type="text" name="email" id="login-email" required placeholder=" " autocomplete="username">
                    <label for="login-email">Login lub e-mail</label>
                </div>
                
                <div class="pjm-input-group icon-group">
                    <span class="material-symbols-rounded icon">lock</span>
                    <input type="password" name="password" id="login-pass" required placeholder=" " autocomplete="current-password">
                    <label for="login-pass">Hasło</label>
                </div>

                <div class="auth-actions-row">
                    <a href="#" class="auth-link auth-trigger" data-target="password">Zapomniałeś hasła?</a>
                </div>

                <button type="submit" class="pjm-btn-submit">
                    <span>Zaloguj się</span> <span class="material-symbols-rounded">login</span>
                </button>
            </form>

            <div class="auth-footer">
                Nie masz konta? <a href="#" class="auth-link auth-trigger" data-target="register">Załóż darmowe konto</a>
            </div>
        </div>

        <div id="view-register" class="pjm-auth-view" style="display:none;">
            <div class="auth-header">
                <h1>Utwórz konto</h1>
                <p>Wypełnij dane, aby założyć konto.</p>
            </div>

            <div class="pjm-role-switcher">
                <label class="switch-btn active" data-target="private">
                    <input type="radio" name="role_toggle" value="private" checked> 
                    <span class="material-symbols-rounded">person</span> Osoba
                </label>
                <label class="switch-btn" data-target="institution">
                    <input type="radio" name="role_toggle" value="institution"> 
                    <span class="material-symbols-rounded">domain</span> Firma
                </label>
                <div class="switch-slider"></div> 
            </div>

            <form id="pjm-register-form" class="pjm-form">
                <input type="hidden" name="nonce" value="<?php echo $auth_nonce; ?>">
                <input type="hidden" name="action" value="pjm_register_init">
                <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">

                <div id="section-private" class="role-section active">
                    <div class="form-row">
                        <div class="pjm-input-group">
                            <input type="text" name="first_name" id="reg-name" placeholder=" ">
                            <label for="reg-name">Imię</label>
                        </div>
                        <div class="pjm-input-group">
                            <input type="text" name="last_name" id="reg-surname" placeholder=" ">
                            <label for="reg-surname">Nazwisko</label>
                        </div>
                    </div>
                </div>

                <div id="section-institution" class="role-section" style="display:none;">
                    <div class="pjm-input-group">
                        <input type="text" name="org_name" id="reg-org" placeholder=" ">
                        <label for="reg-org">Nazwa firmy / Instytucji *</label>
                    </div>
                    <div class="pjm-input-group">
                        <input type="text" name="org_nip" id="reg-nip" placeholder=" ">
                        <label for="reg-nip">NIP *</label>
                    </div>

                    <h4 class="section-title">Osoba kontaktowa</h4>
                    <div class="form-row">
                        <div class="pjm-input-group">
                            <input type="text" name="cont_name" id="cont-name" placeholder=" ">
                            <label for="cont-name">Imię</label>
                        </div>
                        <div class="pjm-input-group">
                            <input type="text" name="cont_last" id="cont-last" placeholder=" ">
                            <label for="cont-last">Nazwisko</label>
                        </div>
                    </div>
                </div>

                <div class="pjm-input-group icon-group mt-4">
                    <span class="material-symbols-rounded icon">email</span>
                    <input type="email" name="user_email" id="reg-email" required placeholder=" ">
                    <label for="reg-email">Adres e-mail</label>
                </div>

                <div class="form-row">
                    <div class="pjm-input-group icon-group">
                        <span class="material-symbols-rounded icon">lock</span>
                        <input type="password" id="reg-pass" name="user_pass" required placeholder=" ">
                        <label for="reg-pass">Hasło</label>
                    </div>
                    <div class="pjm-input-group icon-group">
                        <span class="material-symbols-rounded icon">lock_reset</span>
                        <input type="password" id="reg-pass-confirm" name="user_pass_confirm" required placeholder=" ">
                        <label for="reg-pass-confirm">Powtórz hasło</label>
                    </div>
                </div>

                <div class="pjm-password-validator">
                    <div class="validator-item" data-rule="min8"><span class="material-symbols-rounded">circle</span> Min. 8 znaków</div>
                    <div class="validator-item" data-rule="upper"><span class="material-symbols-rounded">circle</span> Wielka litera</div>
                    <div class="validator-item" data-rule="digit"><span class="material-symbols-rounded">circle</span> Cyfra</div>
                </div>

                <button type="submit" class="pjm-btn-submit">
                    <span>Zarejestruj się</span> <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </form>

            <div class="auth-footer">
                Masz już konto? <a href="#" class="auth-link auth-trigger" data-target="login">Zaloguj się</a>
            </div>
        </div>

        <div id="view-password" class="pjm-auth-view" style="display:none;">
            
            <div id="pass-step-1">
                <div class="auth-header">
                    <h1>Odzyskaj hasło</h1>
                    <p>Podaj e-mail, aby otrzymać kod weryfikacyjny.</p>
                </div>
                <form id="pjm-reset-request-form" class="pjm-form">
                    <input type="hidden" name="nonce" value="<?php echo $auth_nonce; ?>">
                    <input type="hidden" name="action" value="pjm_send_reset_code">

                    <div class="pjm-input-group icon-group">
                        <span class="material-symbols-rounded icon">email</span>
                        <input type="email" name="email" id="reset-email" required placeholder=" ">
                        <label for="reset-email">Adres E-mail</label>
                    </div>
                    <button type="submit" class="pjm-btn-submit"><span>Wyślij kod</span> <span class="material-symbols-rounded">send</span></button>
                </form>
            </div>

            <div id="pass-step-2" style="display:none;">
                <div class="auth-header">
                    <h3>Weryfikacja</h3>
                    <p>Wpisz 6-cyfrowy kod, który wysłaliśmy na Twój e-mail.</p>
                </div>
                
                <form id="pjm-verify-form" class="pjm-form">
                    <input type="hidden" name="nonce" value="<?php echo $auth_nonce; ?>">
                    <input type="hidden" name="action" value="pjm_verify_and_change_password">
                    <input type="hidden" name="email" id="verify-email-hidden">
                    <input type="hidden" name="context" id="verify-context" value="reset"> 
                    <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">

                    <div class="otp-container">
                        <input type="text" name="otp_code" maxlength="6" placeholder="0 0 0 0 0 0" autocomplete="one-time-code" required>
                    </div>

                    <div id="new-pass-field" style="display:none; margin-top:20px;">
                        <div class="pjm-input-group icon-group">
                            <span class="material-symbols-rounded icon">key</span>
                            <input type="password" name="new_pass" id="new-pass" placeholder=" ">
                            <label for="new-pass">Nowe hasło</label>
                        </div>
                    </div>

                    <button type="submit" class="pjm-btn-submit"><span>Zatwierdź</span> <span class="material-symbols-rounded">check_circle</span></button>
                </form>
            </div>
            
            <div class="auth-footer"><a href="#" class="auth-link auth-trigger" data-target="login">Wróć do logowania</a></div>
        </div>

    </div> 
</div>