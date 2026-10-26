const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const context = vm.createContext({});
vm.runInContext(fs.readFileSync(path.join(__dirname, '../../public/js/surya-branding-insights.js'), 'utf8'), context);
const {selectOutlets, qualityOf} = context.SuryaBrandingInsights;
const rows = [
    {id_outlet:'1',nama_outlet:'ALFA',sf:'ARIFIN AMRIZAL',tap:'BENGKALIS',has_front_photo:true,map_visit_state:'verified',branding_dominance:'Tsel',branding_quality:'Bagus'},
    {id_outlet:'2',nama_outlet:'BETA',sf:'ARIFIN AMRIZAL',tap:'BENGKALIS',has_front_photo:true,map_visit_state:'rejected',branding_dominance:'Tsel',branding_quality:'Tidak Bagus'},
    {id_outlet:'3',nama_outlet:'GAMMA',sf:'RIMAWAN',tap:'BENGKALIS',has_front_photo:false},
    {id_outlet:'4',nama_outlet:'DELTA',sf:'GUSTI',tap:'BAGAN BATU',has_front_photo:true,map_visit_state:'verified',branding_dominance:'Tsel',branding_quality:'Bagus'},
    {id_outlet:'5',nama_outlet:'EPSILON',sf:'RIMAWAN',tap:'BENGKALIS',has_front_photo:true,branding_dominance:'No Branding',branding_quality:'Bagus'},
    {id_outlet:'6',nama_outlet:'ZETA',sf:'RIMAWAN',tap:'BENGKALIS',has_front_photo:true,branding_dominance:'Indosat-Tri',branding_quality:'Kurang Bagus'},
];
const ids = filters => Array.from(selectOutlets(rows, filters), row => row.id_outlet);

test('visit drilldown uses the same photo-based counts as the insight, independently from approval', () => {
    assert.deepEqual(ids({tap:'BENGKALIS',visit:'visited'}), ['1','2','5','6']);
    assert.deepEqual(ids({tap:'BENGKALIS',visit:'unvisited'}), ['3']);
    assert.equal(selectOutlets(rows,{visit:'visited'}).length, rows.filter(row=>row.has_front_photo).length);
});
test('operator and quality are separate filters, including Tsel with poor branding', () => {
    assert.deepEqual(ids({tap:'BENGKALIS',brand:'Tsel'}), ['1','2']);
    assert.deepEqual(ids({tap:'BENGKALIS',brand:'Tsel',quality:'Bagus'}), ['1']);
    assert.deepEqual(ids({brand:'Tsel',quality:'Tidak Bagus'}), ['2']);
    assert.deepEqual(ids({brand:'Indosat-Tri',quality:'Kurang Bagus'}), ['6']);
});
test('no branding is not counted as good quality even when a legacy row still contains a quality value', () => {
    assert.equal(qualityOf(rows[4]), 'not_applicable');
    assert.deepEqual(ids({quality:'not_applicable'}), ['5']);
    assert.deepEqual(ids({brand:'No Branding',quality:'Bagus'}), []);
});
test('missing surveys remain distinct from explicitly choosing no branding', () => {
    assert.deepEqual(ids({brand:'missing'}), ['3']);
    assert.deepEqual(ids({quality:'missing'}), ['3']);
    assert.deepEqual(ids({brand:'No Branding'}), ['5']);
});
test('search supports outlet ID, name and sales and combines with TAP and survey filters', () => {
    assert.deepEqual(ids({search:' arifin  alfa ',tap:'BENGKALIS',brand:'Tsel'}), ['1']);
    assert.deepEqual(ids({search:'3'}), ['3']);
    assert.deepEqual(ids({search:'gusti',tap:'BENGKALIS'}), []);
    assert.deepEqual(ids({brand:'XL-Smart'}), []);
});
test('opening filtered lists does not mutate the shared map data', () => {
    const before = JSON.stringify(rows);
    selectOutlets(rows,{visit:'visited'});
    assert.equal(JSON.stringify(rows),before);
    assert.deepEqual(Array.from(selectOutlets([],{})),[]);
});

test('photo metadata is requested once per visible batch and reused briefly across filters', async () => {
    let calls = 0, time = 100;
    const loader = context.SuryaBrandingInsights.createPhotoLoader(async ids => {
        calls++;
        assert.deepEqual(Array.from(ids), calls === 1 ? ['1','2'] : ['1']);
        return {'1':{url:'/storage/front/1.webp'}};
    }, () => time);
    const first = await loader(['1','1','2']);
    assert.equal(first['1'].url, '/storage/front/1.webp');
    assert.equal(first['2'],null);
    await loader(['1','2']);
    assert.equal(calls,1);
    time += 30001;
    await loader(['1']);
    assert.equal(calls,2);
});
test('failed or cancelled photo batches do not poison the retry cache', async () => {
    let calls = 0;
    const loader = context.SuryaBrandingInsights.createPhotoLoader(async () => {
        calls++;
        if (calls === 1) throw new Error('offline');
        return {'1':{url:'/storage/front/new.webp'}};
    });
    await assert.rejects(loader(['1']), /offline/);
    await assert.rejects(loader(['1'],{aborted:true}), /Aborted/);
    assert.equal((await loader(['1']))['1'].url, '/storage/front/new.webp');
    assert.equal(calls,3);
    await loader(['1'],undefined,true);
    assert.equal(calls,4);
});
