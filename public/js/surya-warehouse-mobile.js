(function(root) {
    'use strict';
    const number = value => new Intl.NumberFormat('id-ID').format(Number(value) || 0);
    // Yajra escapes string columns for the table. Decode its entities once,
    // then insert via textContent so names stay readable without rendering HTML.
    function ownerText(value) {
        const entities = {'&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&apos;': "'"};
        return String(value ?? '').replace(/&(?:amp|lt|gt|quot|apos|#0*39);/g, entity => entities[entity] ?? "'");
    }
    function element(tag, className, text) {
        const node = document.createElement(tag);
        node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }
    function quantities(row) {
        const block = element('dl', 'warehouse-quantities');
        for (const [label, key] of [['TOTAL PV', 'grand_total_pv'], ['TOTAL SA', 'grand_total_sa']]) {
            const item = element('div', 'warehouse-quantity');
            item.append(element('dt', '', label), element('dd', '', number(row[key])));
            block.append(item);
        }
        return block;
    }
    function breakdown(row, groups, activeDenoms) {
        const active = new Set(activeDenoms.map(String));
        const sections = [];
        for (const [category, validities] of Object.entries(groups)) {
            const entries = [];
            for (const [validity, denoms] of Object.entries(validities)) {
                const items = denoms.filter(d => active.has(String(d.id)) && Number(row[d.id]));
                if (items.length) entries.push({validity, items, total: items.reduce((sum, d) => sum + Number(row[d.id]), 0)});
            }
            if (entries.length) sections.push({category, entries});
        }
        return sections;
    }
    function card(row, mode, groups, activeDenoms) {
        const item = element('article', 'warehouse-owner-card');
        item.append(element('p', 'warehouse-owner-type', mode === 'sf' ? 'SALES FORCE' : 'TAP'));
        item.append(element('h2', 'warehouse-owner-name', ownerText(mode === 'sf' ? row.sf_name || row.idsf : row.idtap)));
        if (mode === 'sf') item.append(element('p', 'warehouse-owner-tap', ownerText(row.idtap)));
        item.append(quantities(row));
        const details = element('details', 'warehouse-breakdown');
        details.append(element('summary', '', 'Rincian produk'));
        let loaded = false;
        details.addEventListener('toggle', function() {
            if (!details.open || loaded) return;
            loaded = true;
            const sections = breakdown(row, groups, activeDenoms);
            if (!sections.length) {
                details.append(element('p', 'warehouse-detail-note', 'Stok seluruh produk 0.'));
                return;
            }
            for (const section of sections) {
                const group = element('section', 'warehouse-category');
                group.append(element('h3', '', section.category === 'REGULER' ? 'PV REGULER' : section.category === 'BYU' ? 'PV BY.U' : section.category));
                for (const entry of section.entries) {
                    const heading = element('div', 'warehouse-validity');
                    heading.append(element('span', '', entry.validity), element('strong', '', number(entry.total)));
                    group.append(heading);
                    const list = element('dl', 'warehouse-denoms');
                    for (const denom of entry.items) {
                        const line = element('div', '');
                        line.append(element('dt', '', denom.label), element('dd', '', number(row[denom.id])));
                        list.append(line);
                    }
                    group.append(list);
                }
                details.append(group);
            }
            details.append(element('p', 'warehouse-detail-note', 'Denom dengan stok 0 tidak ditampilkan.'));
        });
        item.append(details);
        return item;
    }
    function create(container, groups, showClusters) {
        return {
            loading() {
                container.setAttribute('aria-busy', 'true');
                container.replaceChildren(element('p', 'warehouse-state', 'Memuat stok…'));
            },
            fail(retry) {
                container.setAttribute('aria-busy', 'false');
                const state = element('div', 'warehouse-state warehouse-error');
                state.append(element('p', '', 'Stok gagal dimuat. Periksa koneksi lalu coba lagi.'));
                const button = element('button', 'warehouse-retry', 'Coba lagi');
                button.type = 'button';
                button.addEventListener('click', retry);
                state.append(button);
                container.replaceChildren(state);
            },
            render(payload, rows) {
                container.setAttribute('aria-busy', 'false');
                const nodes = [];
                if (rows.length) {
                    const total = rows.reduce((sum, row) => ({
                        grand_total_pv: sum.grand_total_pv + (Number(row.grand_total_pv) || 0),
                        grand_total_sa: sum.grand_total_sa + (Number(row.grand_total_sa) || 0),
                    }), {grand_total_pv: 0, grand_total_sa: 0});
                    const summary = element('div', 'warehouse-page-summary');
                    summary.append(element('p', '', 'STOK DI HALAMAN INI'), quantities(total));
                    nodes.push(summary);
                    for (const row of rows) nodes.push(card(row, payload.mode, groups, payload.active_denoms || []));
                } else nodes.push(element('p', 'warehouse-state', 'Stok tidak ditemukan. Coba ubah pencarian atau tanggal.'));
                if (showClusters && Object.keys(payload.cluster_totals || {}).length) {
                    const details = element('details', 'warehouse-cluster-summary');
                    details.append(element('summary', '', 'Total per cluster'));
                    details.append(element('p', 'warehouse-detail-note', 'Seluruh data sesuai mode dan tanggal, termasuk di luar halaman atau pencarian ini.'));
                    const names = {'CLUSTER DUMAI': 'DUMAI BENGKALIS', 'CLUSTER ROHIL': 'ROKAN HILIR'};
                    for (const [cluster, total] of Object.entries(payload.cluster_totals)) {
                        details.append(element('h3', '', names[cluster] || cluster), quantities(total));
                    }
                    nodes.push(details);
                }
                container.replaceChildren(...nodes);
            },
        };
    }
    root.SuryaWarehouseMobile = {create, breakdown, ownerText};
})(window);
