const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/surya-visit-upload.js'), 'utf8');

function browser() {
    const requests = [];
    class Xhr {
        constructor() {this.upload = {}; this.headers = {}; requests.push(this);}
        open(method, url) {this.method = method; this.url = url;}
        setRequestHeader(key, value) {this.headers[key] = value;}
        send(data) {this.data = data;}
        respond(status, body) {this.status = status; this.responseText = JSON.stringify(body); this.onload();}
    }
    const window = {};
    vm.runInNewContext(source, {window, XMLHttpRequest: Xhr, FormData: class {constructor(form) {this.form = form;}}});
    return {...window.SuryaVisitUpload, requests};
}

test('compresses only oversized photos further without resizing away outlet details', async () => {
    const b = browser(), qualities = [];
    const canvas = {toBlob(callback, type, quality) {qualities.push(quality); assert.equal(type, 'image/jpeg'); callback({size: quality > .64 ? 500000 : 250000});}};
    assert.equal((await b.compress(canvas)).size, 250000);
    assert.deepEqual(qualities, [.76, .64]);
});

test('small photos need only one encode and encoding failure is surfaced', async () => {
    const b = browser();
    let encodes = 0;
    await b.compress({toBlob(callback) {encodes++; callback({size: 100000});}});
    assert.equal(encodes, 1);
    await assert.rejects(b.compress({toBlob(callback) {callback(null);}}), /gagal dikompresi/);
});

test('progress distinguishes transfer from a confirmed server save', async () => {
    const b = browser(), progress = [], form = {action: '/mobile/visit', photos: ['front', 'inside']};
    const pending = b.send(form, value => progress.push(value));
    const request = b.requests[0];
    assert.equal(request.method, 'POST');
    assert.equal(request.headers.Accept, 'application/json');
    assert.equal(request.data.form, form);
    assert.equal(request.timeout, 120000);
    request.upload.onprogress({lengthComputable: true, loaded: 40, total: 100});
    request.upload.onload();
    assert.deepEqual(progress, [0, 40, 100]);
    request.respond(200, {redirect_url: '/mobile/visit/menu?id_outlet=123'});
    assert.equal((await pending).redirect_url, '/mobile/visit/menu?id_outlet=123');
});

test('a timeout keeps the form photos and never automatically repeats the POST', async () => {
    const b = browser(), form = {action: '/mobile/visit', photos: ['front', 'inside']};
    const pending = b.send(form, () => {});
    b.requests[0].ontimeout();
    await assert.rejects(pending, /Cek status visit/);
    assert.equal(b.requests.length, 1);
    assert.deepEqual(form.photos, ['front', 'inside']);
});

test('server validation and expired sessions reach the form without a page reload', async () => {
    const b = browser();
    const invalid = b.send({action: '/mobile/visit'}, () => {});
    b.requests[0].respond(422, {errors: {branding_dominance: ['Pilih dominasi branding.']}});
    await assert.rejects(invalid, /Pilih dominasi branding/);
    const expired = b.send({action: '/mobile/visit'}, () => {});
    b.requests[1].respond(401, {message: 'Sesi berakhir', redirect: '/mobile/login?timeout=1'});
    await assert.rejects(expired, error => error.redirect === '/mobile/login?timeout=1');
});

test('HTML errors and payload limits cannot be mistaken for a successful save', async () => {
    const b = browser();
    const html = b.send({action: '/mobile/visit'}, () => {});
    b.requests[0].status = 500;
    b.requests[0].responseText = '<html>500</html>';
    b.requests[0].onload();
    await assert.rejects(html, /belum mengonfirmasi/);
    const large = b.send({action: '/mobile/visit'}, () => {});
    b.requests[1].respond(413, {});
    await assert.rejects(large, /batas server/);
});
