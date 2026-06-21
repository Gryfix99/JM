jQuery(document).ready(function ($) {

    // 1. Role Switcher
    $('.switch-btn').on('click', function () {
        $('.switch-btn').removeClass('active');
        $(this).addClass('active');
        $(this).find('input').prop('checked', true);

        const target = $(this).data('target');
        $('.role-section').hide();
        $('#section-' + target).fadeIn(200);
    });

    // 2. Drag & Drop Upload
    $('.pjm-upload-box').on('click', function () {
        $(this).find('input').click();
    });
    $('.hidden-input').on('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            const $preview = $(this).siblings('.preview-area');
            reader.onload = (e) => $preview.html(`<img src="${e.target.result}">`);
            reader.readAsDataURL(this.files[0]);
        }
    });

    // 3. Submit Rejestracji (Init)
    $('#pjm-register-form').on('submit', function (e) {
        e.preventDefault();
        const email = $(this).find('input[name="user_email"]').val();

        if (typeof handleLoginRedirect === 'function') handleLoginRedirect(true);

        pjmAuthAjax($(this), 'pjm_register_init', function (res) {
            // Sukces: Przejdź do weryfikacji
            $('#verify-email-hidden').val(email);
            $('#verify-context').val('register'); // Ustawiamy kontekst
            $('#new-pass-field').hide(); // Przy rejestracji nie podajemy nowego hasła tutaj (było w kroku 1)

            pjmSwitchAuth('password'); // Przełącz na widok hasła/weryfikacji
            $('#pass-step-1').hide();
            $('#pass-step-2').fadeIn();
        });
    });
});