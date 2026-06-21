jQuery(document).ready(function ($) {
    $('#pjm-login-form').on('submit', function (e) {
        e.preventDefault();

        // Jeśli jesteśmy w kalkulatorze (jest funkcja save), zapisz stan
        if (typeof handleLoginRedirect === 'function') handleLoginRedirect(true);

        pjmAuthAjax($(this), 'pjm_login_user', function (res) {
            // Sukces: Przeładuj stronę (zalogowany user wróci do kontekstu)
            window.location.reload();
        });
    });
});