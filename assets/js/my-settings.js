document.addEventListener('DOMContentLoaded', function () {

    // =========================================================
    // 1. OBSŁUGA WYBIERAKA KOLORÓW (Color Picker)
    // =========================================================
    const colorInput = document.getElementById('bg_color_input');
    const hexInput = document.getElementById('bg_color_hex');

    if (colorInput && hexInput) {
        // Gdy użytkownik wybiera kolor na palecie -> aktualizuj pole tekstowe
        colorInput.addEventListener('input', function (e) {
            hexInput.value = e.target.value.toUpperCase();
        });

        // (Opcjonalnie) Gdy użytkownik wpisze HEX ręcznie -> zaktualizuj paletę
        hexInput.addEventListener('input', function (e) {
            const val = e.target.value;
            // Sprawdź czy to poprawny hex (np. #FFFFFF)
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                colorInput.value = val;
            }
        });
    }

    // =========================================================
    // 2. UNIWERSALNA FUNKCJA PODGLĄDU OBRAZKA
    // =========================================================
    function setupImagePreview(inputId, previewSelector, containerSelector) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('change', function (e) {
            const file = e.target.files[0];

            // Jeśli nie wybrano pliku
            if (!file) return;

            // Prosta walidacja typu pliku po stronie przeglądarki
            if (!file.type.match('image.*')) {
                alert('Proszę wybrać plik graficzny (JPG, PNG).');
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                // Znajdź kontener podglądu
                const wrapper = input.closest(containerSelector);
                const previewArea = wrapper.querySelector(previewSelector);

                if (!previewArea) return;

                // Sprawdź czy obrazek już tam jest
                let img = previewArea.querySelector('img');

                if (img) {
                    // Podmień źródło istniejącego obrazka
                    img.src = event.target.result;
                    // Usuń srcset (ważne przy awatarach WP, żeby nie nadpisywały)
                    img.removeAttribute('srcset');
                } else {
                    // Jeśli nie ma obrazka (jest np. placeholder), stwórz nowy
                    const placeholder = previewArea.querySelector('.icon-placeholder');
                    if (placeholder) placeholder.remove();

                    img = document.createElement('img');
                    img.src = event.target.result;

                    // Style inline dla nowo utworzonego elementu (dla pewności)
                    if (inputId === 'user_avatar') {
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                    } else {
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '100%';
                        img.style.objectFit = 'contain';
                    }

                    previewArea.appendChild(img);
                }

                // Dodaj klasę do wrappera (np. żeby zmienić obramowanie)
                wrapper.classList.add('has-file');
            };

            // Czytaj plik jako URL (DataURI)
            reader.readAsDataURL(file);
        });
    }

    // =========================================================
    // 3. INICJALIZACJA PODGLĄDÓW
    // =========================================================

    // Dla Logo Firmowego
    // input id="company_logo", preview class=".preview-area", wrapper class=".file-upload-preview"
    setupImagePreview('company_logo', '.preview-area', '.file-upload-preview');

    // Dla Awatara Użytkownika
    // input id="user_avatar", preview class=".avatar-preview", wrapper class=".avatar-upload-wrapper"
    setupImagePreview('user_avatar', '.avatar-preview', '.avatar-upload-wrapper');

});