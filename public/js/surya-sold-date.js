(() => {
    'use strict';
    const input = document.getElementById('sold-date');
    if (!input || typeof window.flatpickr !== 'function') return;

    window.flatpickr(input, {
        locale: window.flatpickr.l10ns.id,
        dateFormat: 'Y-m-d',
        altInput: true,
        altInputClass: 'pt-date-input',
        altFormat: 'j M Y',
        ariaDateFormat: 'j F Y',
        maxDate: input.max,
        disableMobile: true,
        animate: true,
        onReady: (_dates, _date, instance) => instance.calendarContainer.classList.add('outlet-date-calendar'),
    });
})();
