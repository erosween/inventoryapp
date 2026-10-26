const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../public/js/surya-metrics-upload.js'), 'utf8');
function load() {
    const context = vm.createContext({});
    vm.runInContext(source, context);
    return context.SuryaMetricsUpload.runBatches;
}
test('processes sequential cursor batches and stops after completion', async () => {
    const run = load(), requests = [], progress = [];
    await run(async cursor => {
        requests.push(cursor);
        return { next_row: cursor + 1000, status: cursor === 1002 ? 'complete' : 'processing' };
    }, { next_row: 2, status: 'processing' }, state => progress.push(state.next_row));
    assert.deepEqual(requests, [2, 1002]);
    assert.deepEqual(progress, [2, 1002, 2002]);
});
test('network failure preserves last confirmed progress for an explicit resume', async () => {
    const run = load();
    let last, count = 0;
    await assert.rejects(run(async cursor => {
        if (++count === 2) throw new Error('offline');
        return { next_row: cursor + 1000, status: 'processing' };
    }, { next_row: 2, status: 'processing' }, state => { last = state; }), /offline/);
    assert.equal(last.next_row, 1002);
    const requested = [];
    await run(async cursor => {
        requested.push(cursor);
        return { next_row: 2002, status: 'complete' };
    }, last, state => { last = state; });
    assert.deepEqual(requested, [1002]);
    assert.equal(last.status, 'complete');
});
test('completed or cancelled jobs cannot start another import batch', async () => {
    const run = load();
    let calls = 0;
    await run(async () => calls++, { status: 'complete' }, () => {});
    await assert.rejects(run(async () => calls++, { status: 'cancelled' }, () => {}), /dihentikan/);
    assert.equal(calls, 0);
});

