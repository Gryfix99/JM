document.addEventListener('DOMContentLoaded', function () {

    // Elementy
    const radios = document.querySelectorAll('input[name="client_type"]');
    const companyWrapper = document.getElementById('company-fields-wrapper');
    const companyNameInput = document.getElementById('company_name');
    const companyNipInput = document.getElementById('company_nip');

    if (!radios.length || !companyWrapper) return;

    // Funkcja przełączająca
    function toggleFields() {
        // Znajdź zaznaczony radio
        const selected = document.querySelector('input[name="client_type"]:checked').value;

        if (selected === 'company') {
            // Pokaż pola
            companyWrapper.classList.remove('hidden-fields');

            // Ustaw jako wymagane (HTML5 validation)
            if (companyNameInput) companyNameInput.setAttribute('required', 'required');
            if (companyNipInput) companyNipInput.setAttribute('required', 'required');

            // Lekka animacja wejścia (opcjonalne)
            companyWrapper.style.opacity = 0;
            setTimeout(() => companyWrapper.style.opacity = 1, 10);
            companyWrapper.style.transition = 'opacity 0.3s ease';

        } else {
            // Ukryj pola
            companyWrapper.classList.add('hidden-fields');

            // Zdejmij wymagania
            if (companyNameInput) companyNameInput.removeAttribute('required');
            if (companyNipInput) companyNipInput.removeAttribute('required');
        }
    }

    // Nasłuchiwanie zmian
    radios.forEach(radio => {
        radio.addEventListener('change', toggleFields);
    });

    // Uruchom raz na starcie (żeby ustawić stan początkowy)
    toggleFields();

});