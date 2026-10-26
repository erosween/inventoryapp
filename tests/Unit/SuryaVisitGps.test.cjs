const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/mobile/visit.blade.php'), 'utf8');
const source = blade.slice(blade.indexOf('let visitGpsPromise = null;'), blade.indexOf('\nrefreshVisitLocation().catch'));
const submissionSource = blade.slice(blade.indexOf("$('#visit-form').on('submit'"), blade.indexOf("\nwindow.addEventListener('pagehide'"));

function browser() {
    const requests = [], watches = [], clearedWatches = [], values = new Map(), handlers = new Map(), alerts = [];
    let submitted = 0;
    let now = 1792990000000;
    let status = '';
    const context = vm.createContext({
        Date: {now: () => now},
        navigator: {geolocation: {
            getCurrentPosition: (success, failure, options) => requests.push({success, failure, options}),
            watchPosition: (success, failure, options) => {watches.push({success, failure, options}); return watches.length - 1;},
            clearWatch: id => clearedWatches.push(id),
        }},
        document: {getElementById: () => ({files: [{}], href: 'https://example.test/mobile/visit/menu', dataset: {menuUrl: 'https://example.test/mobile/visit/menu'}, classList: {add() {}, remove() {}}})},
        URL,
        FormData: class {get() {return '1201073824';}},
        SuryaVisitUpload: {send: async () => {submitted++; return {redirect_url: '/mobile/visit/menu'};}},
        window: {location: {assign() {}}},
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
        requests, watches, clearedWatches, values, get now() {return now;}, advance: ms => now += ms,
        read: () => vm.runInContext('refreshVisitLocation()', context), status: () => status,
        startWatch: () => vm.runInContext('startVisitGpsWatch()', context),
        stopWatch: () => vm.runInContext('stopVisitGpsWatch()', context),
        photoLocation: () => vm.runInContext('getVisitPhotoLocation("GPS foto siap.")', context),
        problem: error => {context.fixtureError = error; return vm.runInContext('visitGpsProblem(fixtureError)', context);},
        photo: (kind, position) => vm.runInContext(`rememberVisitPhotoLocation(${JSON.stringify(kind)}, ${JSON.stringify(position)})`, context),
        submitLocation: () => vm.runInContext('applyVisitPhotoLocation()', context),
        submit: () => handlers.get('#visit-form:submit').call({setAttribute() {}, removeAttribute() {}}, {preventDefault() {}}),
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
    b.requests[1].success({coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 7}, timestamp: b.now - 31000});
    await rejected;
    assert.equal(b.values.get('#visit-latitude'), '');
    assert.equal(b.values.get('#visit-longitude'), '');
});

test('overlapping initial and photo requests share one GPS read rather than cancel each other', async () => {
    const b = browser();
    const first = b.read();
    const second = b.photoLocation();
    assert.equal(b.requests.length, 1);
    b.requests[0].success({coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now});
    await Promise.all([first, second]);
    assert.equal(b.values.get('#visit-latitude'), 1.480325);
    assert.equal(b.values.get('#visit-longitude'), 102.1108652);
});

test('a temporary GPS timeout retries once at high accuracy and can recover', async () => {
    const b = browser();
    const pending = b.photoLocation();
    b.requests[0].failure({code: 3});
    assert.equal(b.requests.length, 2);
    assert.equal(b.requests[1].options.enableHighAccuracy, true);
    assert.equal(b.requests[1].options.maximumAge, 0);
    b.requests[1].success({coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now});
    assert.equal((await pending).coords.latitude, 1.480325);
    assert.match(b.status(), /GPS:/);
});

test('camera fixes within 10 seconds are reused immediately but older fixes require a new read', async () => {
    const b = browser();
    b.startWatch();
    b.watches[0].success({coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now});
    await b.photoLocation();
    assert.equal(b.requests.length, 0);
    b.advance(11000);
    const pending = b.photoLocation();
    assert.equal(b.requests.length, 1);
    b.requests[0].success({coords: {latitude: 1.49, longitude: 102.12, accuracy: 8}, timestamp: b.now});
    assert.equal((await pending).coords.latitude, 1.49);
});

test('a delayed one-shot reading cannot replace a newer measured camera fix', async () => {
    const b = browser();
    const pending = b.read();
    const earlier = b.now;
    b.advance(1000);
    b.startWatch();
    b.watches[0].success({coords: {latitude: 1.480325, longitude: 102.1108652, accuracy: 7}, timestamp: b.now});
    b.requests[0].success({coords: {latitude: 0.53449532, longitude: 101.4211399, accuracy: 100}, timestamp: earlier});
    assert.equal((await pending).coords.latitude, 1.480325);
});

test('permission denial discards a recent fix and cannot silently fall back to it', async () => {
    const b = browser();
    b.startWatch();
    b.watches[0].success({coords: {latitude: 1.48, longitude: 102.11, accuracy: 7}, timestamp: b.now});
    b.watches[0].failure({code: 1});
    assert.deepEqual(b.clearedWatches, [0]);
    const pending = b.photoLocation();
    b.requests[0].failure({code: 1});
    await assert.rejects(pending, error => error.code === 1);
    assert.equal(b.requests.length, 1);
    assert.equal(b.values.get('#visit-latitude'), '');
    assert.equal(b.problem({code: 1}).title, 'Izin Lokasi Diblokir');
});

test('a callback queued before permission was revoked cannot restore the denied fix', async () => {
    const b = browser();
    const pending = b.read();
    b.startWatch();
    b.watches[0].failure({code: 1});
    b.requests[0].success({coords: {latitude: 1.48, longitude: 102.11, accuracy: 7}, timestamp: b.now});
    await assert.rejects(pending, error => error.code === 1);
    assert.equal(b.values.get('#visit-latitude'), '');
});

test('a stopped camera watcher cannot overwrite a new camera location', () => {
    const b = browser();
    b.startWatch();
    b.stopWatch();
    b.startWatch();
    b.watches[1].success({coords: {latitude: 1.48, longitude: 102.11, accuracy: 7}, timestamp: b.now});
    b.watches[0].success({coords: {latitude: 0.53, longitude: 101.42, accuracy: 7}, timestamp: b.now});
    assert.equal(b.values.get('#visit-latitude'), 1.48);
});

test('a fresh measured watch fix can rescue a one-shot timeout without using stale GPS', async () => {
    const b = browser();
    b.startWatch();
    const pending = b.photoLocation();
    b.requests[0].failure({code: 3});
    b.watches[0].success({coords: {latitude: 1.48, longitude: 102.11, accuracy: 7}, timestamp: b.now});
    b.requests[1].failure({code: 3});
    assert.equal((await pending).coords.latitude, 1.48);
    assert.equal(b.requests.length, 2);
});

test('unresolved timeouts have a distinct message and stop after one retry', async () => {
    const b = browser();
    const pending = b.photoLocation();
    b.requests[0].failure({code: 3});
    b.requests[1].failure({code: 3});
    await assert.rejects(pending, error => error.code === 3);
    assert.equal(b.requests.length, 2);
    assert.equal(b.problem({code: 3}).title, 'Lokasi Belum Terbaca');
    assert.equal(b.problem({code: 2}).title, 'Lokasi Belum Tersedia');
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
