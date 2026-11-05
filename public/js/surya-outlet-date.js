(() => {
    'use strict';
    const input = document.getElementById('sales-cutoff');
    if (!input) return;
    const parse = value => { const [y,m,d] = value.split('-').map(Number); return new Date(y,m-1,d,12); };
    const iso = date => [date.getFullYear(),String(date.getMonth()+1).padStart(2,'0'),String(date.getDate()).padStart(2,'0')].join('-');
    const caption = document.querySelector('[data-period-caption]');
    const updateCaption = date => {
        if (!date || !caption) return;
        const today = parse(input.max);
        const lastDay = new Date(date.getFullYear(),date.getMonth()+1,0).getDate();
        const closed = date.getDate() === lastDay && date < new Date(today.getFullYear(),today.getMonth(),1);
        caption.textContent = closed
            ? 'Bulan penuh · ' + new Intl.DateTimeFormat('id-ID',{month:'long',year:'numeric'}).format(date)
            : 'MTD 1–' + new Intl.DateTimeFormat('id-ID',{day:'numeric',month:'long',year:'numeric'}).format(date);
    };
    let picker;
    if (typeof window.flatpickr === 'function') {
        picker = window.flatpickr(input, {
            locale: window.flatpickr.l10ns.id,
            dateFormat: 'Y-m-d', altInput: true, altFormat: 'j F Y', ariaDateFormat: 'j F Y',
            maxDate: input.max, disableMobile: true, position: 'auto right',
            onReady: (_, __, instance) => {
                instance.calendarContainer.classList.add('outlet-date-calendar');
                instance.currentYearElement.setAttribute('aria-label','Tahun');
                instance.monthsDropdownContainer?.setAttribute('aria-label','Bulan');
                instance.altInput.id = 'sales-cutoff-display';
                instance.altInput.setAttribute('aria-label','Tanggal cutoff laporan');
                instance.altInput.setAttribute('aria-describedby','outlet-period-caption');
                document.querySelector('label[for="sales-cutoff"]').htmlFor = instance.altInput.id;
                instance.altInput.setAttribute('inputmode','none');
            },
            onChange: dates => updateCaption(dates[0]),
        });
    }
    document.querySelector('[data-open-outlet-date]')?.addEventListener('click', () => {
        if (picker) picker.open();
        else if (input.showPicker) input.showPicker();
        else input.focus();
    });
    document.querySelectorAll('[data-period-preset]').forEach(button => button.addEventListener('click', () => {
        const today = parse(input.max);
        const date = button.dataset.periodPreset === 'latest' ? parse(input.dataset.latest) : new Date(today.getFullYear(),today.getMonth(),0,12);
        if (picker) picker.setDate(date,true);
        else { input.value = iso(date); updateCaption(date); }
    }));
    input.addEventListener('change', () => { if (input.value) updateCaption(parse(input.value)); });
    window.addEventListener('scroll', event => {
        if (picker?.isOpen && !picker.calendarContainer.contains(event.target)) picker.close();
    }, {passive:true,capture:true});
})();
