<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="jm-pricing-section jm-wrapper">

    <header class="jm-seo-box">
        <span class="jm-badge-pill">KONTAKT</span>
        <div class="jm-header-area">
            <h1>Skontaktuj się z nami</h1>
        </div>
        <div class="jm-seo-desc">
            <p>
                Masz pytania dotyczące tłumaczeń PJM, wyceny lub współpracy?
                Napisz do nas lub zadzwoń &mdash; odpowiemy najszybciej jak to możliwe.
            </p>
        </div>
    </header>

    <section class="jm-benefits-grid">
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">call</span>
            <h4>Telefon</h4>
            <p><a href="tel:+48722150487" style="color:inherit; text-decoration:none;">722 150 487</a></p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">mail</span>
            <h4>E-mail</h4>
            <p><a href="mailto:janusz@januszmigowego.pl" style="color:inherit; text-decoration:none;">janusz@januszmigowego.pl</a></p>
        </div>
        <div class="jm-benefit-item">
            <span class="material-symbols-rounded" aria-hidden="true">location_on</span>
            <h4>Adres</h4>
            <p>Jana Olbrachta 120/44<br>01-373 Warszawa</p>
        </div>
    </section>

    <div class="jm-contact-grid" style="margin-top: 40px;">
        <div class="jm-contact-form-col">
            <form class="pjm-contact-form" id="pjm-contact-form" novalidate>
                <div class="pjm-cf-row">
                    <div class="pjm-cf-group">
                        <label for="pjm-cf-name">Imię i nazwisko <span>*</span></label>
                        <input type="text" id="pjm-cf-name" name="name" required>
                    </div>
                    <div class="pjm-cf-group">
                        <label for="pjm-cf-email">E-mail <span>*</span></label>
                        <input type="email" id="pjm-cf-email" name="email" required>
                    </div>
                </div>
                <div class="pjm-cf-row">
                    <div class="pjm-cf-group">
                        <label for="pjm-cf-phone">Telefon</label>
                        <input type="text" id="pjm-cf-phone" name="phone" placeholder="+48...">
                    </div>
                    <div class="pjm-cf-group">
                        <label for="pjm-cf-subject">Temat</label>
                        <input type="text" id="pjm-cf-subject" name="subject" placeholder="np. Wycena tłumaczenia">
                    </div>
                </div>
                <div class="pjm-cf-group">
                    <label for="pjm-cf-message">Wiadomość <span>*</span></label>
                    <textarea id="pjm-cf-message" name="message" rows="5" required></textarea>
                </div>
                <label class="pjm-cf-consent">
                    <input type="checkbox" name="consent" required>
                    <span>Wyrażam zgodę na przetwarzanie moich danych w celu odpowiedzi na zapytanie (<a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>" target="_blank" rel="noopener">Polityka prywatności</a>).</span>
                </label>
                <!-- honeypot: ukryte pole anty-spam (boty je wypełniają) -->
                <input type="text" name="pjm_company" id="pjm-cf-hp" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">
                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'pjm_contact' ) ); ?>">
                <button type="submit" class="pjm-cf-submit">Wyślij wiadomość</button>
                <div class="pjm-cf-msg" id="pjm-cf-msg" role="status" aria-live="polite"></div>
            </form>
        </div>

        <div class="jm-contact-info-col">
            <div class="jm-contact-widget">
                <h3 class="jm-widget-title">Godziny pracy</h3>
                <p class="jm-widget-desc">Odpowiadamy na wiadomości w dni robocze. Pilne zlecenia realizujemy również w weekendy.</p>

                <div class="jm-contact-row">
                    <div class="jm-icon-circle"><span class="material-symbols-outlined">schedule</span></div>
                    <span>Pon &ndash; Pt: 9:00 &ndash; 17:00</span>
                </div>

                <div class="jm-contact-row">
                    <div class="jm-icon-circle"><span class="material-symbols-outlined">call</span></div>
                    <a href="tel:+48722150487" class="jm-contact-link">722 150 487</a>
                </div>

                <div class="jm-contact-row">
                    <div class="jm-icon-circle"><span class="material-symbols-outlined">mail</span></div>
                    <a href="mailto:janusz@januszmigowego.pl" class="jm-contact-link">janusz@januszmigowego.pl</a>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.pjm-contact-form { background:#fff; border:1px solid #E3E1D9; border-radius:16px; padding:26px; }
.pjm-cf-row { display:flex; gap:16px; flex-wrap:wrap; }
.pjm-cf-row .pjm-cf-group { flex:1; min-width:200px; }
.pjm-cf-group { margin-bottom:16px; }
.pjm-cf-group label { display:block; font-size:13px; font-weight:600; color:#16241D; margin-bottom:6px; }
.pjm-cf-group label span { color:#C0392B; }
.pjm-contact-form input, .pjm-contact-form textarea { width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid #d8ded9; border-radius:10px; font-size:14px; font-family:inherit; color:#16241D; transition:border-color .15s, box-shadow .15s; }
.pjm-contact-form input:focus, .pjm-contact-form textarea:focus { outline:none; border-color:#1B5E4B; box-shadow:0 0 0 3px rgba(27,94,75,.12); }
.pjm-cf-consent { display:flex; gap:10px; align-items:flex-start; font-size:12px; color:#5f6f67; margin:6px 0 18px; }
.pjm-cf-consent input { width:auto; margin-top:2px; }
.pjm-cf-consent a { color:#1B5E4B; text-decoration:underline; }
.pjm-cf-submit { background:#1B5E4B; color:#fff; border:none; border-radius:12px; padding:14px 30px; font-weight:700; font-size:15px; cursor:pointer; }
.pjm-cf-submit:hover { background:#154A3B; } .pjm-cf-submit:disabled { opacity:.6; cursor:default; }
.pjm-cf-msg { margin-top:14px; font-size:14px; font-weight:600; }
.pjm-cf-msg.ok { color:#15803d; } .pjm-cf-msg.err { color:#b91c1c; }
</style>

<script>
jQuery(function($){
    var ajax = (window.pjm_vars && pjm_vars.ajax_url) ? pjm_vars.ajax_url : '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    $('#pjm-contact-form').on('submit', function(e){
        e.preventDefault();
        var $f = $(this), $msg = $('#pjm-cf-msg'), $btn = $f.find('.pjm-cf-submit');
        $msg.removeClass('ok err').text('');
        if (!$f[0].checkValidity()) { $msg.addClass('err').text('Uzupełnij wymagane pola i zgodę.'); return; }
        $btn.prop('disabled', true).text('Wysyłanie…');
        $.post(ajax, {
            action: 'pjm_contact_submit',
            nonce: $f.find('[name=nonce]').val(),
            name: $f.find('[name=name]').val(),
            email: $f.find('[name=email]').val(),
            phone: $f.find('[name=phone]').val(),
            subject: $f.find('[name=subject]').val(),
            message: $f.find('[name=message]').val(),
            pjm_company: $f.find('[name=pjm_company]').val()
        }, function(res){
            if (res && res.success){ $f[0].reset(); $msg.addClass('ok').text(res.data && res.data.message ? res.data.message : 'Wiadomość wysłana.'); }
            else { $msg.addClass('err').text('Błąd: ' + ((res && res.data) || 'spróbuj ponownie.')); }
        }).fail(function(){ $msg.addClass('err').text('Błąd połączenia. Spróbuj ponownie.'); })
          .always(function(){ $btn.prop('disabled', false).text('Wyślij wiadomość'); });
    });
});
</script>
