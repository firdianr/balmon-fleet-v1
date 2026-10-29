import 'flowbite';
import Alpine from 'alpinejs';

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

window.Alpine = Alpine;
Alpine.start();

// Inisialisasi Flatpickr Bahasa Indonesia
flatpickr.localize(Indonesian);

document.addEventListener('DOMContentLoaded', () => {
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');

    if (startDateEl && endDateEl) {
        const inputStyle = "block w-full ps-9 pe-3 py-2.5 bg-white border border-gray-300 text-gray-900 text-xs font-semibold rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs placeholder:text-gray-400";

        const endPicker = flatpickr(endDateEl, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            altInputClass: inputStyle,
            minDate: "today"
        });

        flatpickr(startDateEl, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            altInputClass: inputStyle,
            minDate: "today",
            onChange: function(selectedDates, dateStr) {
                endPicker.set("minDate", dateStr);
            }
        });
    }
});