(() => {
    'use strict';

    const input = document.getElementById('visit-date');
    if (!input) return;

    const caption = document.querySelector('[data-visit-period-caption]');
    const parse = value => {
        if (!value) return null;
        const [year, month, day] = value.split('-').map(Number);
        return new Date(year, month - 1, day, 12);
    };
    const toIso = date => [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
    const updateCaption = date => {
        if (!date || !caption) return;
        caption.textContent = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        }).format(date);
    };

    let picker;
    if (typeof window.flatpickr === 'function') {
        picker = window.flatpickr(input, {
            locale: window.flatpickr.l10ns.id,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j F Y',
            ariaDateFormat: 'j F Y',
            maxDate: input.max,
            disableMobile: true,
            position: 'auto right',
            onReady: (_, __, instance) => {
                instance.calendarContainer.classList.add('outlet-date-calendar');
                instance.currentYearElement.setAttribute('aria-label', 'Tahun');
                instance.monthsDropdownContainer?.setAttribute('aria-label', 'Bulan');
                instance.altInput.id = 'visit-date-display';
                instance.altInput.setAttribute('aria-label', 'Tanggal laporan visit');
                instance.altInput.setAttribute('aria-describedby', 'visit-period-caption');
                document.querySelector('label[for="visit-date"]').htmlFor = instance.altInput.id;
                instance.altInput.setAttribute('inputmode', 'none');
            },
            onChange: dates => updateCaption(dates[0]),
        });
    }

    document.querySelector('[data-open-visit-date]')?.addEventListener('click', () => {
        if (picker) picker.open();
        else if (input.showPicker) input.showPicker();
        else input.focus();
    });

    document.querySelectorAll('[data-visit-date-preset]').forEach(button => {
        button.addEventListener('click', () => {
            const date = parse(input.max);
            if (!date) return;
            if (button.dataset.visitDatePreset === 'yesterday') date.setDate(date.getDate() - 1);
            const value = toIso(date);
            if (picker) picker.setDate(value, true);
            else {
                input.value = value;
                updateCaption(date);
            }
        });
    });

    input.addEventListener('change', () => updateCaption(parse(input.value)));
    window.addEventListener('scroll', event => {
        if (picker?.isOpen && !picker.calendarContainer.contains(event.target)) picker.close();
    }, { passive: true, capture: true });
})();
