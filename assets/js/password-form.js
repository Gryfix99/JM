jQuery(document).ready(function ($) {

    // 1. Inicjacja Resetu Hasła (Wysyłka kodu)
    $('#pjm-reset-request-form').on('submit', function (e) {
        e.preventDefault();
        const email = $(this).find('input[name="email"]').val();

        pjmAuthAjax($(this), 'pjm_send_reset_code', function (res) {
            // Sukces: Przejdź do kroku 2
            $('#verify-email-hidden').val(email);
            $('#verify-context').val('reset');
            $('#new-pass-field').show(); // Przy resecie pokazujemy pole "Nowe hasło"

            $('#pass-step-1').hide();
            $('#pass-step-2').fadeIn();
        });
    });

    // 2. Weryfikacja OTP (Wspólna dla Rejestracji i Resetu)
    $('#pjm-verify-form').on('submit', function (e) {
        e.preventDefault();
        const context = $('#verify-context').val();
        const action = (context === 'register') ? 'pjm_register_verify' : 'pjm_verify_and_change_password';

        pjmAuthAjax($(this), action, function (res) {
            alert(res.data.message || 'Sukces!');
            window.location.reload(); // Zalogowano
        });
    });
});