/**
 * PJM General JS
 * Funkcje używane globalnie w całym systemie.
 */

(function ($) {
    window.PJM = window.PJM || {};

    // 1. Zabezpieczenie przed XSS (używane wszędzie przy wyświetlaniu danych usera)
    PJM.escapeHtml = function (text) {
        if (!text) return text;
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    // 2. Formatowanie waluty
    PJM.formatPrice = function (amount) {
        return Math.round(amount) + " zł";
    };

    // 3. Helper do dat (dodawanie dni roboczych)
    PJM.addWorkDays = function (startDate, days, skipWeekends) {
        let currentDate = new Date(startDate);
        let added = 0;
        while (added < days) {
            currentDate.setDate(currentDate.getDate() + 1);
            if (skipWeekends) {
                if (currentDate.getDay() !== 0 && currentDate.getDay() !== 6) {
                    added++;
                }
            } else {
                added++;
            }
        }
        return currentDate;
    };

    // 4. Prosty Toast/Alert (zamiast brzydkiego alert())
    PJM.toast = function (message, type = 'success') {
        // Można tu wpiąć ładną bibliotekę toastr, na razie fallback do alert dla błędów
        if (type === 'error') alert(message);
        else console.log('PJM Info:', message);
    };

})(jQuery);