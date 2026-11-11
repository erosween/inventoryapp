(() => {
    const regions = JSON.parse(document.querySelector('#svp-demo-data').textContent);
    const segments = ['mobile', 'household'];
    const period = document.querySelector('#svp-period');
    const editor = document.querySelector('#svp-editor');
    const form = document.querySelector('#svp-form');
    const field = name => form.elements.namedItem(name);
    const number = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });
    const scoreFormat = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    const monthFormat = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' });
    const storageKey = 'surya-kpi-svp-periods-v1';
    let saved = {};
    let editing;

    try {
        saved = JSON.parse(localStorage.getItem(storageKey)) ?? {};
    } catch {
        document.querySelector('#svp-storage-note').textContent = 'Data berlaku selama halaman terbuka.';
    }

    function rowsFor(regionId, segmentId) {
        const region = regions[regionId];
        return saved[period.value]?.[regionId]?.[segmentId] ?? region[segmentId].map(
            values => period.value === '2026-10' ? values : [null, null]
        );
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
        const mobileRows = rowsFor(regionId, 'mobile').map(calculate);
        const householdRows = rowsFor(regionId, 'household').map(calculate);
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
            card.querySelector('[data-segment-score]').textContent = showScore(score);
            setClass(document.querySelector(`[data-summary-class="${key}"]`), resultClass(score));
            setClass(card.querySelector('[data-segment-class]'), resultClass(score));

            card.querySelectorAll('[data-svp-metric]').forEach((row, index) => {
                const result = rows[index];
                const unit = row.dataset.unit;
                const progress = row.querySelector('progress');
                row.querySelector('[data-target]').textContent = format(result.target, unit);
                row.querySelector('[data-actual]').textContent = format(result.actual, unit);
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

    renderPeriod();
    period.addEventListener('change', () => {
        if (!period.value) period.value = period.defaultValue;
        renderPeriod();
    });

    const switches = document.querySelectorAll('[data-svp-region]');
    const panels = document.querySelectorAll('[data-svp-panel]');
    switches.forEach(button => button.addEventListener('click', () => {
        const active = button.dataset.svpRegion;
        switches.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        panels.forEach(panel => { panel.hidden = panel.dataset.svpPanel !== active; });
    }));

    document.querySelectorAll('[data-svp-edit]').forEach(button => button.addEventListener('click', () => {
        editing = button.dataset.svpEdit;
        const month = monthFormat.format(new Date(period.value + '-01T00:00:00Z'));
        document.querySelector('#svp-editor-region').textContent = regions[editing].name + ' · ' + month;
        for (const id of segments) {
            rowsFor(editing, id).forEach(([target, actual], index) => {
                field(`${id}-${index}-target`).value = target ?? '';
                field(`${id}-${index}-actual`).value = actual ?? '';
            });
        }
        editor.showModal();
        editor.querySelector('.svp-form-body').scrollTop = 0;
    }));

    editor.querySelector('[data-svp-close]').addEventListener('click', () => editor.close());
    form.addEventListener('submit', event => {
        event.preventDefault();
        const entry = {};
        for (const id of segments) {
            entry[id] = rowsFor(editing, id).map((_, index) => ['target', 'actual'].map(key => {
                const value = field(`${id}-${index}-${key}`).valueAsNumber;
                return Number.isFinite(value) ? value : null;
            }));
        }
        saved[period.value] ??= {};
        saved[period.value][editing] = entry;
        try {
            localStorage.setItem(storageKey, JSON.stringify(saved));
        } catch {
            document.querySelector('#svp-storage-note').textContent = 'Data berlaku selama halaman terbuka.';
        }
        renderRegion(editing);
        editor.close();
    });
})();
