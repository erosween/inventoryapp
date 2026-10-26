const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/monitadumai/index.blade.php'), 'utf8');
const popupSource = blade.slice(blade.indexOf('        function buildOutletPopup('), blade.indexOf('        function escapeMapDetailHtml('));
const keySource = blade.slice(blade.indexOf('        function handleKeyDown('), blade.indexOf('        function updateSelection('));
const fitSource = blade.slice(blade.indexOf('        function fitAndQueueOutletMarkers('), blade.indexOf('        function queueOutletMarkers('));

test('a quick search remains focused when the full outlet payload arrives later', () => {
    let fitted = 0, queued = 0;
    const context = vm.createContext({selectedOutletMarker:null,map:{fitBounds:()=>fitted++},queueOutletMarkers:()=>queued++});
    vm.runInContext(fitSource,context);
    vm.runInContext('fitAndQueueOutletMarkers([{latitude:1.48,longitude:102.11}])',context);
    assert.equal(fitted,1);
    context.selectedOutletMarker={};
    vm.runInContext('fitAndQueueOutletMarkers([{latitude:1.48,longitude:102.11}])',context);
    assert.equal(fitted,1);
    assert.equal(queued,2);
});

test('a lightweight marker shows pending metrics rather than fabricated zero sales', () => {
    const context = vm.createContext({userLocationLatLng: null, escapeMapDetailHtml: value => String(value), mapDetailDate: () => '-'});
    vm.runInContext(popupSource, context);
    const html = vm.runInContext('buildOutletPopup({id_outlet:"1", nama_outlet:"OUTLET", tap:"DUMAI"}, {lat:1.48,lng:102.11})', context);
    assert.equal((html.match(/<strong>…<\/strong>/g) || []).length, 3);
    assert.match(html, /Memuat foto dan rincian/);
    const detail = vm.runInContext('buildOutletPopup({id_outlet:"1", nama_outlet:"OUTLET", tap:"DUMAI",m_cvm:0,m_stsa:7,m_stpv:3,front_photo_url:null}, {lat:1.48,lng:102.11})', context);
    assert.match(detail, /<strong>0<\/strong>/);
    assert.match(detail, /<strong>7<\/strong>/);
    assert.match(detail, /<strong>3<\/strong>/);
    assert.doesNotMatch(detail, /<strong>…<\/strong>/);
});

test('Enter searches immediately while autocomplete is still empty or hidden', () => {
    let searched = 0, prevented = 0;
    const context = vm.createContext({
        document: {getElementById: () => ({style:{display:'none'},querySelectorAll:()=>[]})},
        searchOutlet:()=>searched++, selectedIndex:-1,
    });
    vm.runInContext(keySource, context);
    context.event = {key:'Enter',preventDefault:()=>prevented++};
    vm.runInContext('handleKeyDown(event)',context);
    assert.equal(searched, 1);
    assert.equal(prevented, 1);
});

test('Enter still opens the selected autocomplete result without also searching twice', () => {
    let selected = 0, searched = 0;
    const context = vm.createContext({
        document:{getElementById:()=>({style:{display:'block'},querySelectorAll:()=>[{click:()=>selected++}]})},
        searchOutlet:()=>searched++, selectedIndex:0,
    });
    vm.runInContext(keySource,context);
    context.event={key:'Enter',preventDefault(){}};
    vm.runInContext('handleKeyDown(event)',context);
    assert.equal(selected,1);
    assert.equal(searched,0);
});
