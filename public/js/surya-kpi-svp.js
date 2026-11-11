(() => {
    const regions = JSON.parse(document.querySelector('#svp-demo-data').textContent);
    const period = document.querySelector('#svp-period');
    const status = document.querySelector('#svp-status');
    const forms = [...document.querySelectorAll('.svp-inline-form')];
    const number = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });
    const scoreFormat = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    const cutoffFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
    const entries = new Map();
    let localEntries = {};
    let request;
    let loaded = false;
    let mode = 'mtd';
    let editing = null;

    if (forms.length) {
        try {
            localEntries = JSON.parse(localStorage.getItem('surya-kpi-svp-periods-v1')) ?? {};
        } catch {
            localEntries = {};
        }
    }

    function monthEnd(month) {
        const end = new Date(month + '-01T00:00:00Z');
        end.setUTCMonth(end.getUTCMonth() + 1, 0);
        return end;
    }

    function previousMonth() {
        const date = new Date(period.value + '-01T00:00:00Z');
        date.setUTCMonth(date.getUTCMonth() - 1);
        return date.toISOString().slice(0, 7);
    }

    function key(month, region, segment, metric) {
        return [month, region, segment, metric].join(':');
    }

    function remember(entry) {
        entries.set(key(entry.period.slice(0, 7), entry.region, entry.segment, entry.metric), {
            target: entry.target === null ? null : Number(entry.target),
            actual: entry.actual === null ? null : Number(entry.actual),
            date: entry.actual_date,
        });
    }

    function entryFor(month, region, segment, metric, includeLocal = false) {
        const shared = entries.get(key(month, region, segment, metric));
        if (shared) return shared;

        const legacy = includeLocal ? localEntries[month]?.[region] : null;
        const local = legacy?.[segment]?.[metric];
        if (local) return { target: local[0], actual: local[1], date: legacy.actualDate ?? null };

        if (month === '2026-10') {
            const [target, actual] = regions[region][segment][metric];
            return { target, actual, date: monthEnd(month).toISOString().slice(0, 10) };
        }
        return null;
    }

    function calculate([target, actual]) {
        const gap = target === null || actual === null ? null : actual - target;
        const achievement = target > 0 && actual !== null ? actual / target : null;
        const score = achievement === null ? null : achievement < 0.95 ? 1 : achievement <= 1 ? 2 : 3;
        return { target, actual, gap, achievement, score };
    }

    function resultClass(score) {
        if (score === null) return '—';
        const bands = [[2.6, 'Platinum'], [2.2, 'Gold'], [1.8, 'Silver'], [1.4, 'Bronze'], [1, 'Black']];
        return bands.find(([threshold]) => score >= threshold)[1];
    }

    function setClass(element, value) {
        element.textContent = value;
        element.dataset.class = value.toLowerCase();
    }

    function format(value, unit) {
        if (value === null) return '—';
        if (unit === 'Rp') return 'Rp ' + number.format(value);
        return number.format(value) + unit;
    }

    function showScore(score) {
        return score === null ? '—' : scoreFormat.format(score);
    }

    function renderRegion(regionId) {
        const isProjection = mode === 'projection';
        const daysInMonth = monthEnd(period.value).getUTCDate();
        const panel = document.querySelector(`[data-svp-panel="${regionId}"]`);
        panel.querySelectorAll('[data-svp-action]').forEach(element => { element.hidden = isProjection; });
        panel.querySelector('[data-svp-cutoff]').textContent = isProjection
            ? 'Proyeksi sampai akhir bulan berdasarkan tanggal update tiap KPI.'
            : '';

        const results = segment => regions[regionId][segment].map((_, index) => {
            const entry = entryFor(period.value, regionId, segment, index);
            const { target, actual, date } = entry ?? { target: null, actual: null, date: null };
            // Market Share and C3MR are rates, so they retain their actual values.
            const value = isProjection && index < 2 && actual !== null
                ? (date ? actual * daysInMonth / Number(date.slice(-2)) : null)
                : actual;
            return { ...calculate([target, value]), date };
        });
        const mobileRows = results('mobile');
        const householdRows = results('household');
        const [all, broadband, marketShare] = mobileRows.map(row => row.score);
        const [newSales, ps, c3mr] = householdRows.map(row => row.score);
        const weight = regions[regionId].revenueWeight;
        const revenuePoints = all === null || broadband === null ? null : all * 40 + broadband * 60;
        const mobile = revenuePoints === null || marketShare === null
            ? null : (revenuePoints * weight + marketShare * (100 - weight) * 100) / 10000;
        const newSalesPsPoints = newSales === null || ps === null ? null : newSales * 60 + ps * 40;
        const household = newSalesPsPoints === null || c3mr === null
            ? null : (newSalesPsPoints * 55 + c3mr * 45 * 100) / 10000;
        document.querySelector(`[data-svp-segment="${regionId}-mobile"] [data-revenue-score]`).textContent =
            showScore(revenuePoints === null ? null : revenuePoints / 100);
        document.querySelector(`[data-svp-segment="${regionId}-household"] [data-new-sales-ps-score]`).textContent =
            showScore(newSalesPsPoints === null ? null : newSalesPsPoints / 100);
        const complete = mobile !== null && household !== null;
        const finalClass = resultClass(complete ? Math.min(mobile, household) : null);
        const matrix = document.querySelector(`[data-svp-matrix="${regionId}"]`);
        setClass(document.querySelector(`[data-region-class="${regionId}"]`), finalClass);
        setClass(matrix.querySelector('[data-matrix-class]'), finalClass);
        matrix.querySelector('[data-matrix-mobile]').textContent = showScore(mobile);
        matrix.querySelector('[data-matrix-household]').textContent = showScore(household);
        const point = matrix.querySelector('[data-matrix-point]');
        point.hidden = !complete;
        if (complete) {
            point.style.left = (household - 1) * 50 + '%';
            point.style.top = (3 - mobile) * 50 + '%';
        }

        for (const [segmentId, score, rows] of [['mobile', mobile, mobileRows], ['household', household, householdRows]]) {
            const key = regionId + '-' + segmentId;
            const card = document.querySelector(`[data-svp-segment="${key}"]`);
            document.querySelector(`[data-summary-score="${key}"]`).textContent = showScore(score);
            card.querySelector('[data-svp-actual-heading]').textContent = isProjection ? 'Proyeksi / Aktual' : 'Aktual MTD';
            card.querySelector('[data-segment-score]').textContent = showScore(score);
            setClass(document.querySelector(`[data-summary-class="${key}"]`), resultClass(score));
            setClass(card.querySelector('[data-segment-class]'), resultClass(score));

            card.querySelectorAll('[data-svp-metric]').forEach((row, index) => {
                const result = rows[index];
                const unit = row.dataset.unit;
                const progress = row.querySelector('progress');
                row.querySelector('[data-target]').textContent = format(result.target, unit);
                row.querySelector('[data-actual]').textContent = format(result.actual, unit);
                row.querySelector('[data-actual-date]').textContent = result.date
                    ? cutoffFormat.format(new Date(result.date + 'T00:00:00Z')) : '—';
                const form = row.querySelector('.svp-inline-form');
                if (form) {
                    const canEdit = !isProjection && loaded;
                    const isEditing = canEdit && editing === form;
                    row.querySelector('[data-svp-edit]').hidden = !canEdit || isEditing;
                    form.hidden = !isEditing;
                    row.querySelectorAll('.svp-inline-input').forEach(input => { input.hidden = !isEditing; });
                    row.querySelectorAll('[data-target], [data-actual], [data-actual-date]').forEach(element => {
                        element.hidden = isEditing;
                    });
                }
                row.querySelector('[data-score]').textContent = result.score ?? '—';
                row.querySelector('[data-ach]').textContent = result.achievement === null ? '—' : (result.achievement * 100).toFixed(1) + '%';
                row.querySelector('[data-gap]').textContent = format(result.gap, unit);
                row.dataset.trend = result.gap === null ? '' : result.gap < 0 ? 'below' : 'met';
                progress.hidden = result.achievement === null;
                progress.value = (result.achievement ?? 0) * 100;
            });
        }
    }

    function renderPeriod() {
        for (const regionId of Object.keys(regions)) renderRegion(regionId);
    }

    const modes = document.querySelectorAll('[data-svp-mode]');
    modes.forEach(button => button.addEventListener('click', () => {
        mode = button.dataset.svpMode;
        modes.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        renderPeriod();
    }));

    function fillEditor(form) {
        const { region, segment, metric } = form.dataset;
        const entry = entryFor(period.value, region, segment, metric, true);
        const previous = entryFor(previousMonth(), region, segment, metric, true);
        form.elements.target.value = (entry ? entry.target : previous?.target) ?? '';
        form.elements.actual.value = entry?.actual ?? '';
        const date = form.elements['actual-date'];
        date.min = period.value + '-01';
        date.max = monthEnd(period.value).toISOString().slice(0, 10);
        date.value = entry?.date ?? '';
        date.required = form.elements.actual.value !== '';
    }

    async function loadPeriod() {
        request?.abort();
        const controller = new AbortController();
        request = controller;
        loaded = false;
        editing = null;
        renderPeriod();
        status.textContent = 'Memuat data KPI…';
        const url = new URL(period.dataset.entriesUrl);
        url.searchParams.set('period', period.value);
        try {
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat data KPI.');
            const rows = await response.json();
            if (request !== controller) return;
            entries.clear();
            rows.forEach(remember);
            loaded = true;
            renderPeriod();
            status.textContent = '';
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Gagal memuat data KPI. Muat ulang halaman untuk mencoba lagi.';
        }
    }

    period.addEventListener('change', () => {
        if (!period.checkValidity()) period.value = period.defaultValue;
        loadPeriod();
    });

    const switches = document.querySelectorAll('[data-svp-region]');
    const panels = document.querySelectorAll('[data-svp-panel]');
    switches.forEach(button => button.addEventListener('click', () => {
        const active = button.dataset.svpRegion;
        switches.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        panels.forEach(panel => { panel.hidden = panel.dataset.svpPanel !== active; });
    }));

    forms.forEach(form => {
        const actual = form.elements.actual;
        const date = form.elements['actual-date'];
        const edit = form.parentElement.querySelector('[data-svp-edit]');
        edit.addEventListener('click', () => {
            editing = form;
            fillEditor(form);
            renderPeriod();
            actual.focus();
        });
        form.querySelector('[data-svp-cancel]').addEventListener('click', () => {
            editing = null;
            renderRegion(form.dataset.region);
            edit.focus();
        });
        actual.addEventListener('input', () => { date.required = actual.value !== ''; });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!loaded || mode !== 'mtd' || editing !== form) return;
            const button = form.querySelector('[type="submit"]');
            const month = period.value;
            button.disabled = true;
            const values = {
                ...form.dataset, period: month,
                target: form.elements.target.value || null,
                actual: actual.value || null,
                actual_date: date.value || null,
            };
            try {
                const response = await fetch(period.dataset.entriesUrl, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(values),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Gagal menyimpan KPI.');
                if (period.value === month) {
                    remember(result);
                    if (editing === form) editing = null;
                    renderRegion(values.region);
                    status.textContent = 'Data KPI tersimpan.';
                }
            } catch (error) {
                status.textContent = error.message;
            } finally {
                button.disabled = false;
            }
        });
    });
    loadPeriod();
})();
