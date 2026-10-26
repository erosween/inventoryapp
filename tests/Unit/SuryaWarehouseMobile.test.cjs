const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const context = {window: {}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/surya-warehouse-mobile.js'), 'utf8'), context);
const breakdown = context.window.SuryaWarehouseMobile.breakdown;
const groups = {
    REGULER: {'1 HARI': [{id: 'PV1', label: 'PV 2GB'}, {id: 'PV2', label: 'PV 4GB'}]},
    BYU: {'1 HARI': [{id: 'BYU1', label: 'BYU 3GB'}]},
    SA: {'3GB': [{id: 'SA1', label: 'SA 3GB'}]},
};

test('mobile breakdown keeps PV reguler, BYU, and SA separate and totals each validity once', () => {
    const result = breakdown({PV1: 12, PV2: 8, BYU1: 3, SA1: 5}, groups, ['PV1', 'PV2', 'BYU1', 'SA1']);
    assert.deepEqual(Array.from(result, s => [s.category, s.entries[0].total]), [['REGULER', 20], ['BYU', 3], ['SA', 5]]);
});

test('hides zero quantities and inactive denoms but preserves negative stock', () => {
    const result = breakdown({PV1: 0, PV2: -4, BYU1: 99, SA1: 2}, groups, ['PV1', 'PV2', 'SA1']);
    assert.deepEqual(Array.from(result, s => [s.category, s.entries[0].total]), [['REGULER', -4], ['SA', 2]]);
    assert.equal(result[0].entries[0].items.length, 1);
});

test('all-zero SF stock produces an explicit empty breakdown and numeric denom IDs still match', () => {
    assert.equal(breakdown({PV1: 0}, groups, ['PV1']).length, 0);
    const result = breakdown({'12': 7}, {SA: {SA: [{id: 12, label: 'SA'}]}}, ['12']);
    assert.equal(result[0].entries[0].total, 7);
});

test('owner names decode the table escape layer exactly once for safe text rendering', () => {
    const decode = context.window.SuryaWarehouseMobile.ownerText;
    assert.equal(decode('QUR&#039;ANI'), "QUR'ANI");
    assert.equal(decode('A &amp; B'), 'A & B');
    assert.equal(decode('&amp;lt;img&amp;gt;'), '&lt;img&gt;');
});
