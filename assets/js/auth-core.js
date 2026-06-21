jQuery(document).ready(function ($) {

    // --- 1. UI HELPERS ---

    // Floating Labels Init (fix dla autouzupełniania przeglądarki)
    const updateInputs = () => {
        $('.pjm-input-group input').each(function () {
            if ($(this).val()) $(this).addClass('has-value');
            else $(this).removeClass('has-value');
        });
    };
    $('.pjm-input-group input').on('change input blur', updateInputs);
    setTimeout(updateInputs, 300); // Opóźnienie dla Chrome Autofill

    // Wyświetlanie komunikatów
    const showMessage = ($form, message, type = 'error') => {
        let $msgBox = $form.find('.pjm-auth-message');
        if ($msgBox.length === 0) {
            $msgBox = $('<div class="pjm-auth-message"></div>');
            $form.find('button[type="submit"]').before($msgBox);
        }
        $msgBox.removeClass('success error').addClass(type)
            .html(`<span class="material-symbols-rounded" style="vertical-align:middle;font-size:16px;margin-right:5px;">${type === 'success' ? 'check_circle' : 'error'}</span> ${message}`)
            .slideDown();
    };

    const clearMessage = ($form) => $form.find('.pjm-auth-message').hide();

    // --- 2. PRZEŁĄCZANIE WIDOKÓW ---
    window.pjmSwitchView = function (viewName) {
        $('.pjm-auth-view').hide();
        $('#view-' + viewName).fadeIn(300);
        clearMessage($('form'));
    };

    $('.auth-trigger').on('click', function (e) {
        e.preventDefault();
        pjmSwitchView($(this).data('target'));
    });

    // --- 3. PRZEŁĄCZNIK OSOBA / FIRMA ---
    $('.switch-btn').on('click', function () {
        $('.switch-btn').removeClass('active');
        $(this).addClass('active');
        $(this).find('input').prop('checked', true);

        const target = $(this).data('target');
        $('.role-section').hide();
        $('#section-' + target).fadeIn(200);
    });

    // --- 4. WALIDATOR HASŁA ---
    $('#reg-pass').on('input', function () {
        const val = $(this).val();
        const rules = {
            min8: val.length >= 8,
            upper: /[A-Z]/.test(val),
            digit: /[0-9]/.test(val)
        };
        for (const [key, isValid] of Object.entries(rules)) {
            const $item = $(`.validator-item[data-rule="${key}"]`);
            if (isValid) $item.addClass('valid').find('span').text('check_circle');
            else $item.removeClass('valid').find('span').text('circle');
        }
    });

    // --- 5. AJAX CORE ---
    function pjmAuthAjax($form, actionName, successCallback) {
        const $btn = $form.find('button[type="submit"]');
        const orgHtml = $btn.html();

        clearMessage($form);
        $btn.prop('disabled', true).html('<span>Przetwarzanie...</span> <span class="material-symbols-rounded spin">sync</span>');

        let formData = new FormData($form[0]);
        formData.set('action', actionName);

        $.ajax({
            url: pjm_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false, processData: false,
            success: function (res) {
                $btn.prop('disabled', false).html(orgHtml);
                if (res.success) successCallback(res);
                else showMessage($form, res.data || 'Błąd', 'error');
            },
            error: function () {
                $btn.prop('disabled', false).html(orgHtml);
                showMessage($form, 'Błąd połączenia.', 'error');
            }
        });
    }

    // --- 6. OBSŁUGA FORMULARZY ---

    // A. Logowanie
    $('#pjm-login-form').on('submit', function (e) {
        e.preventDefault();
        pjmAuthAjax($(this), 'pjm_login_user', function (res) {
            showMessage($('#pjm-login-form'), 'Zalogowano! Przekierowanie...', 'success');
            setTimeout(() => window.location.href = res.data.redirect || '/moje-konto', 1000);
        });
    });

    // B. Rejestracja
    $('#pjm-register-form').on('submit', function (e) {
        e.preventDefault();
        if ($('#reg-pass').val() !== $('#reg-pass-confirm').val()) {
            showMessage($(this), 'Hasła nie są identyczne.', 'error'); return;
        }
        const email = $('#reg-email').val();

        pjmAuthAjax($(this), 'pjm_register_init', function (res) {
            $('#verify-email-hidden').val(email);
            $('#verify-context').val('register');
            $('#new-pass-field').hide();

            pjmSwitchView('password');
            $('#pass-step-1').hide();
            $('#pass-step-2').show();
            showMessage($('#pjm-verify-form'), 'Kod weryfikacyjny wysłany na e-mail.', 'success');
        });
    });

    // C. Reset Hasła
    $('#pjm-reset-request-form').on('submit', function (e) {
        e.preventDefault();
        const email = $('#reset-email').val();
        pjmAuthAjax($(this), 'pjm_send_reset_code', function (res) {
            $('#verify-email-hidden').val(email);
            $('#verify-context').val('reset');
            $('#new-pass-field').show();

            $('#pass-step-1').hide();
            $('#pass-step-2').fadeIn();
            showMessage($('#pjm-verify-form'), 'Kod wysłany. Sprawdź e-mail.', 'success');
        });
    });

    // D. Weryfikacja OTP
    $('#pjm-verify-form').on('submit', function (e) {
        e.preventDefault();
        const context = $('#verify-context').val();
        const action = (context === 'register') ? 'pjm_register_verify' : 'pjm_verify_and_change_password';

        pjmAuthAjax($(this), action, function (res) {
            if (context === 'register') {
                showMessage($('#pjm-verify-form'), 'Konto utworzone! Logowanie...', 'success');
                setTimeout(() => window.location.href = res.data.redirect || '/moje-konto', 1500);
            } else {
                alert('Hasło zmienione. Zaloguj się.');
                pjmSwitchView('login');
                $('#pass-step-2').hide(); $('#pass-step-1').show();
            }
        });
    });

});