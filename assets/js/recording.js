/**
 * Obsługa zakładek (Tabs) dla kalkulatorów Nagrań i Eventów
 */

// Funkcja wywoływana z poziomu HTML (onclick)
function pjmSwitchTab(evt, tabId) {
    if (evt) {
        evt.preventDefault();
    }

    // 1. Ukryj wszystkie treści zakładek
    var panes = document.querySelectorAll('.jm-tab-pane');
    panes.forEach(function (pane) {
        pane.classList.remove('active');

        // Reset animacji (opcjonalny hack, by animacja wjazdu odtwarzała się ponownie)
        pane.style.display = 'none';
        setTimeout(() => {
            if (pane.id === tabId) pane.style.display = 'grid'; // lub flex/block zależnie od CSS
            else pane.style.removeProperty('display');
        }, 10);
    });

    // 2. Usuń klasę aktywną ze wszystkich przycisków
    var triggers = document.querySelectorAll('.jm-tab-trigger');
    triggers.forEach(function (btn) {
        btn.classList.remove('active');
    });

    // 3. Pokaż docelową zakładkę
    var target = document.getElementById(tabId);
    if (target) {
        // Małe opóźnienie dla płynności animacji CSS
        setTimeout(function () {
            target.classList.add('active');
        }, 20);
    } else {
        console.warn('PJM: Nie znaleziono zakładki o ID: ' + tabId);
    }

    // 4. Aktywuj kliknięty przycisk
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    }
}

// Funkcja pomocnicza do przewijania do formularza (używana w Eventach)
function scrollToForm(packageName) {
    // Sprawdź czy na stronie jest formularz kontaktowy
    const contactForm = document.querySelector('form.wpcf7-form, #contact-form');

    if (contactForm) {
        // Przewiń do formularza
        contactForm.scrollIntoView({ behavior: 'smooth' });

        // Jeśli formularz ma pole tematu, spróbuj je wypełnić
        const subjectField = contactForm.querySelector('input[name="your-subject"]');
        const messageField = contactForm.querySelector('textarea[name="your-message"]');

        if (subjectField) {
            subjectField.value = 'Zapytanie o: ' + packageName;
        } else if (messageField) {
            // Jeśli nie ma tematu, dopisz do treści
            messageField.value = 'Dzień dobry, jestem zainteresowany pakietem: ' + packageName + '.\n\n' + messageField.value;
        }
    } else {
        // Jeśli nie ma formularza na tej stronie, przekieruj do kontaktu
        window.location.href = '/kontakt/?subject=' + encodeURIComponent(packageName);
    }
}