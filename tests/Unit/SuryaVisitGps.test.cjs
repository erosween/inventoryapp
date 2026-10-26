const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/mobile/visit.blade.php'), 'utf8');
const source = blade.slice(blade.indexOf('let visitGpsRequest = 0;'), blade.indexOf('\nrefreshVisitLocation().catch'));

function browser() {
    const requests = [], values = new Map();
    const now = 1792990000000;
    let status = '';
    const context = vm.createContext({
        Date: {now: () => now},
        navigator: {geolocation: {getCurrentPosition: (success, failure, options) => requests.push({success, failure, options})}},
        $: selector => {
            const element = {
                val(value) { for (const id of selector.split(',').map(s => s.trim())) values.set(id, value); return element; },
                removeClass() { return element; }, addClass() { return element; },
                text(value) { status = value; return element; },
            };
            return element;
        },
    });
    vm.runInContext(source, context);
    return {requests, values, now, read: () => vm.runInContext('refreshVisitLocation()', context), status: () => status};
}

test('uses fresh measured GPS and shows its accuracy to the sales force', async () => {
    const b = browser();
    const pending = b.read();
    assert.equal(b.requests[0].options.maximumAge, 0);
    assert.equal(b.requests[0].options.enableHighAccuracy, true);
    b.requests[0].success({coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now});
    await pending;
    assert.equal(b.values.get('#visit-latitude'), 1.480325);
    assert.match(b.status(), /akurasi ±7 m/);
});

test('clears old coordinates and refuses a stale position returned by the browser', async () => {
    const b = browser();
    b.values.set('#visit-latitude', 0.53449532);
    const pending = b.read();
    const rejected = assert.rejects(pending, /kedaluwarsa/);
    b.requests[0].success({coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 7}, timestamp: b.now - 31000});
    await rejected;
    assert.equal(b.values.get('#visit-latitude'), '');
    assert.equal(b.values.get('#visit-longitude'), '');
});

test('an earlier GPS callback cannot overwrite a newer measurement', async () => {
    const b = browser();
    const first = b.read();
    const rejected = assert.rejects(first, /digantikan/);
    const second = b.read();
    b.requests[1].success({coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now});
    await second;
    b.requests[0].success({coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 200}, timestamp: b.now});
    await rejected;
    assert.equal(b.values.get('#visit-latitude'), 1.480325);
    assert.equal(b.values.get('#visit-longitude'), 102.1108652);
});
