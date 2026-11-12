(() => {
    const regions = JSON.parse(document.querySelector('#svp-demo-data').textContent);
    const period = document.querySelector('#svp-period');
    const status = document.querySelector('#svp-status');
    const forms = [...document.querySelectorAll('.svp-inline-form')];
    const number = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });
    const simulationFormat = new Intl.NumberFormat('en-US', { maximumFractionDigits: 16 });
    const scoreFormat = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    const cutoffFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
    const entries = new Map();
    const simulations = new Map();
    const suggestedValues = new WeakMap();
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

    function weightedScore(scores, weights) {
        if (scores.includes(null)) return null;
        return scores.reduce((total, score, index) => total + score * weights[index], 0) / 10000;
    }

    function updateGuide(panel, segment, title, rows, weights, goal) {
        const output = panel.querySelector(`[data-svp-guide-segment="${segment}"]`);
        if (rows.some(row => !(row.target > 0))) {
            output.textContent = `${title} · Lengkapi target KPI untuk menghitung kebutuhan.`;
            return;
        }

        const metrics = [...panel.querySelectorAll(`[data-svp-segment$="-${segment}"] [data-svp-metric]`)];
        const goalClass = resultClass(goal);
        const options = rows.map((row, index) => {
            const scale = metrics[index].dataset.unit === '%' ? 100 : 1;
            const silverMinimum = Math.ceil(row.target * 0.95 * scale) / scale;
            const upper = [Math.max(0, silverMinimum - 1 / scale), row.target, Infinity];
            const lower = [0, silverMinimum, (Math.floor(row.target * scale) + 1) / scale];
            return lower.map((minimum, index) => {
                const value = Math.min(upper[index], Math.max(minimum, row.actual ?? minimum));
                return { value, score: calculate([row.target, value]).score };
            });
        });

        // Six metrics are split into two groups of three, so each segment has at most 27 scenarios.
        let best;
        for (const first of options[0]) {
            for (const second of options[1]) {
                for (const third of options[2]) {
                    const combination = [first, second, third];
                    const score = weightedScore(combination.map(option => option.score), weights);
                    if (resultClass(score) !== goalClass) continue;
                    const cost = combination.reduce((total, option, index) => {
                        const row = rows[index];
                        return total + Math.abs(option.value - (row.actual ?? 0)) / row.target;
                    }, 0);
                    if (!best || cost < best.cost) best = { combination, score, cost };
                }
            }
        }

        if (!best) {
            output.textContent = `${title} · Kelas ${goalClass} tidak dapat dibentuk dengan target ini.`;
            return;
        }

        const values = new Map();
        const changes = best.combination.flatMap(({ value }, index) => {
            if (rows[index].actual === value) return [];
            values.set(rows[index].simulationKey, value);
            const unit = metrics[index].dataset.unit;
            return [metrics[index].querySelector('th').textContent + ' = ' + format(value, unit)];
        });
        output.textContent = changes.length
            ? `${title} · ${changes.join(' · ')} → skor ${showScore(best.score)}`
            : `${title} · Sudah di kelas ${goalClass}.`;
        return values;
    }

    function renderRegion(regionId) {
        const isProjection = mode === 'projection';
        const isSimulation = mode === 'simulation';
        const daysInMonth = monthEnd(period.value).getUTCDate();
        const panel = document.querySelector(`[data-svp-panel="${regionId}"]`);
        panel.querySelectorAll('[data-svp-action]').forEach(element => { element.hidden = mode !== 'mtd'; });
        panel.querySelector('[data-svp-cutoff]').textContent = isProjection
            ? 'Proyeksi sampai akhir bulan berdasarkan tanggal update tiap KPI.'
            : '';

        const reset = panel.querySelector('[data-svp-reset]');
        reset.hidden = !isSimulation;
        reset.disabled = !loaded;

        const results = segment => regions[regionId][segment].map((_, index) => {
            const entry = entryFor(period.value, regionId, segment, index);
            const { actual, date } = entry ?? { actual: null, date: null };
            const target = entry ? entry.target : isSimulation
                ? entryFor(previousMonth(), regionId, segment, index)?.target ?? null : null;
            const simulationKey = key(period.value, regionId, segment, index);
            // Market Share and C3MR are rates, so they retain their actual values.
            const baseline = (isProjection || isSimulation) && index < 2 && actual !== null
                ? (date ? actual * daysInMonth / Number(date.slice(-2)) : null)
                : actual;
            const value = isSimulation && simulations.has(simulationKey) ? simulations.get(simulationKey) : baseline;
            return { ...calculate([target, value]), date, simulationKey };
        });
        const mobileRows = results('mobile');
        const householdRows = results('household');
        const weight = regions[regionId].revenueWeight;
        const mobileWeights = [40 * weight, 60 * weight, (100 - weight) * 100];
        const householdWeights = [60 * 55, 40 * 55, 45 * 100];
        const mobileScores = mobileRows.map(row => row.score);
        const householdScores = householdRows.map(row => row.score);
        const [all, broadband] = mobileScores;
        const [newSales, ps] = householdScores;
        const revenuePoints = all === null || broadband === null ? null : all * 40 + broadband * 60;
        const newSalesPsPoints = newSales === null || ps === null ? null : newSales * 60 + ps * 40;
        const mobile = weightedScore(mobileScores, mobileWeights);
        const household = weightedScore(householdScores, householdWeights);
        document.querySelector(`[data-svp-segment="${regionId}-mobile"] [data-revenue-score]`).textContent =
            showScore(revenuePoints === null ? null : revenuePoints / 100);
        document.querySelector(`[data-svp-segment="${regionId}-household"] [data-new-sales-ps-score]`).textContent =
            showScore(newSalesPsPoints === null ? null : newSalesPsPoints / 100);
        const complete = mobile !== null && household !== null;
        const finalClass = resultClass(complete ? Math.min(mobile, household) : null);
        const guide = panel.querySelector('[data-svp-guide]');
        guide.hidden = !isSimulation;
        if (isSimulation) {
            panel.querySelector('[data-svp-cutoff]').textContent =
                'Isi nilai akhir bulan untuk simulasi. Skor berubah saat capaian mencapai 95% atau melewati 100%.';
            const goal = Number(guide.querySelector('[data-svp-goal]').value);
            const mobilePlan = updateGuide(panel, 'mobile', 'Mobile', mobileRows, mobileWeights, goal);
            const householdPlan = updateGuide(panel, 'household', 'Household', householdRows, householdWeights, goal);
            const plan = mobilePlan && householdPlan ? new Map([...mobilePlan, ...householdPlan]) : new Map();
            suggestedValues.set(panel, plan);
            guide.querySelector('[data-svp-apply-goal]').disabled = !loaded || plan.size === 0;
        }
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
            card.querySelector('[data-svp-actual-heading]').textContent = isSimulation ? 'Nilai simulasi' : isProjection ? 'Proyeksi / Aktual' : 'Aktual MTD';
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
                const canEdit = mode === 'mtd' && loaded && form !== null;
                const isEditing = canEdit && editing === form;
                if (form) {
                    row.querySelector('[data-svp-edit]').hidden = !canEdit || isEditing;
                    form.hidden = !isEditing;
                    row.querySelectorAll('.svp-inline-input').forEach(input => { input.hidden = !isEditing; });
                    row.querySelectorAll('[data-target], [data-actual-date]').forEach(element => {
                        element.hidden = isEditing;
                    });
                }
                const simulation = row.querySelector('[data-svp-simulation]');
                simulation.hidden = !isSimulation;
                simulation.disabled = !loaded;
                simulation.dataset.key = result.simulationKey;
                if (document.activeElement !== simulation) {
                    simulation.value = result.actual === null ? '' : simulationFormat.format(result.actual);
                }
                row.querySelector('[data-actual]').hidden = isSimulation || isEditing;
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
    panels.forEach(panel => {
        const region = panel.dataset.svpPanel;
        panel.addEventListener('input', event => {
            const input = event.target;
            if (!input.matches('[data-svp-simulation]')) return;
            const value = input.value.trim();
            const valid = value === '' || /^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d*)?$/.test(value);
            const numeric = value === '' ? null : Number(value.replaceAll(',', ''));
            if (!valid || (numeric !== null && !Number.isFinite(numeric))) return;
            simulations.set(input.dataset.key, numeric);
            renderRegion(region);
        });
        panel.addEventListener('focusin', event => {
            const input = event.target;
            if (input.matches('[data-svp-simulation]')) input.value = input.value.replaceAll(',', '');
        });
        panel.addEventListener('focusout', event => {
            if (event.target.matches('[data-svp-simulation]')) renderRegion(region);
        });
        panel.querySelector('[data-svp-apply-goal]').addEventListener('click', () => {
            for (const [metric, value] of suggestedValues.get(panel)) simulations.set(metric, value);
            renderRegion(region);
        });
        panel.querySelector('[data-svp-goal]').addEventListener('change', () => renderRegion(region));
        panel.querySelector('[data-svp-reset]').addEventListener('click', () => {
            for (const segment of ['mobile', 'household']) {
                regions[region][segment].forEach((_, index) => {
                    simulations.delete(key(period.value, region, segment, index));
                });
            }
            renderRegion(region);
        });
    });
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
