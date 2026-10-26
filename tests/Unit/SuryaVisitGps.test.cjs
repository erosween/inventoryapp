const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/mobile/visit.blade.php'), 'utf8');
const source = blade.slice(blade.indexOf('let visitGpsRequest = 0;'), blade.indexOf('\nrefreshVisitLocation().catch'));
const submissionSource = blade.slice(blade.indexOf("$('#visit-form').on('submit'"), blade.indexOf("\nwindow.addEventListener('pagehide'"));

function browser() {
    const requests = [], values = new Map(), handlers = new Map(), alerts = [];
    let submitted = 0;
    const now = 1792990000000;
    let status = '';
    const context = vm.createContext({
        Date: {now: () => now},
        navigator: {geolocation: {getCurrentPosition: (success, failure, options) => requests.push({success, failure, options})}},
        document: {getElementById: () => ({files: [{}]})},
        Swal: {fire: value => alerts.push(value)},
        $: selector => {
            const element = {
                val(value) { for (const id of selector.split(',').map(s => s.trim())) values.set(id, value); return element; },
                removeClass() { return element; }, addClass() { return element; },
                text(value) { status = value; return element; },
                on(event, handler) { handlers.set(selector + ':' + event, handler); return element; },
                find() { return element; }, prop() { return element; }, html() { return element; },
            };
            return element;
        },
    });
    vm.runInContext(source, context);
    vm.runInContext('let visitSubmitting = false;\n' + submissionSource, context);
    return {
        requests, values, now,
        read: () => vm.runInContext('refreshVisitLocation()', context), status: () => status,
        photo: (kind, position) => vm.runInContext(`rememberVisitPhotoLocation(${JSON.stringify(kind)}, ${JSON.stringify(position)})`, context),
        submitLocation: () => vm.runInContext('applyVisitPhotoLocation()', context),
        submit: () => handlers.get('#visit-form:submit').call({requestSubmit: () => submitted++}, {preventDefault() {}}),
        submitted: () => submitted, alerts,
    };
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

test('submission keeps front photo GPS even when the sales force moves before saving', async () => {
    const b = browser();
    const position = {coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now};
    b.photo('front', position);
    b.photo('inside', {coords: {latitude: 1.4804, longitude: 102.1109, accuracy: 9}, timestamp: b.now});
    const later = b.read();
    b.requests[0].success({coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 7}, timestamp: b.now});
    await later;
    b.submit();
    assert.equal(b.submitted(), 1);
    assert.equal(b.values.get('#visit-latitude'), 1.480325);
    assert.equal(b.values.get('#visit-longitude'), 102.1108652);
    assert.equal(b.values.get('#visit-accuracy'), 7);
    assert.equal(b.requests.length, 1, 'submission must not request a different GPS reading');
});

test('retaking the front photo replaces its old GPS snapshot', () => {
    const b = browser();
    assert.equal(b.submitLocation(), false);
    b.photo('front', {coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 7}});
    b.photo('front', {coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 8}});
    assert.equal(b.submitLocation(), true);
    assert.equal(b.values.get('#visit-latitude'), 1.480325);
    assert.equal(b.values.get('#visit-longitude'), 102.1108652);
    assert.equal(b.values.get('#visit-accuracy'), 8);
});

test('a form without photo-linked GPS cannot submit coordinates left by another GPS read', async () => {
    const b = browser();
    const pending = b.read();
    b.requests[0].success({coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 7}, timestamp: b.now});
    await pending;
    b.submit();
    assert.equal(b.submitted(), 0);
    assert.equal(b.alerts[0].title, 'GPS Foto Belum Tercatat');
});
