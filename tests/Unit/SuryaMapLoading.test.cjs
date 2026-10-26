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
const initSource = blade.slice(blade.indexOf('        function initMap('), blade.indexOf('        function fitAndQueueOutletMarkers('));
const queueSource = blade.slice(blade.indexOf('        function queueOutletMarkers('), blade.indexOf('        function showAllOutletPoints('));
const loadSource = blade.slice(blade.indexOf('        let outletLoadPromise ='), blade.indexOf('        function initMap('));

test('missing cluster library still creates a usable marker layer', async () => {
    let fitCalls = 0;
    const layer = { addTo() { return this; } };
    const map = { setView() { return this; }, invalidateSize() {} };
    const context = vm.createContext({
        map:null, L:{ map:()=>map, tileLayer:()=>({addTo(){}}), layerGroup:()=>layer },
        console:{warn(){}}, requestAnimationFrame:fn=>fn(), setTimeout(){}, window:{},
        addFullscreenControl(){}, showUserLocation(){}, fitAndQueueOutletMarkers:()=>{ fitCalls++; return Promise.resolve(); }
    });
    vm.runInContext(initSource, context);
    await vm.runInContext('initMap([])', context);
    assert.equal(context.outletClusterLayer, layer);
    assert.equal(fitCalls, 1);
});

test('all marker batches finish before loading is marked complete, including without clustering', async () => {
    const frames = [], added = [], outlets = Array.from({length:185}, (_, id) => ({id}));
    const context = vm.createContext({
        Promise, pendingOutletFocus:null, addOutletMarker:outlet=>outlet,
        outletClusterLayer:{addLayer:marker=>added.push(marker.id)},
        window:{requestAnimationFrame:callback=>frames.push(callback)}
    });
    vm.runInContext(queueSource, context);
    let complete = false;
    const pending = context.queueOutletMarkers(outlets).then(()=>{ complete=true; });
    assert.equal(added.length,80);
    assert.equal(complete,false);
    frames.shift()();
    assert.equal(added.length,160);
    assert.equal(complete,false);
    frames.shift()();
    await pending;
    assert.equal(added.length,185);
    assert.equal(complete,true);
});

test('an error in a later animation frame rejects the load rather than silently losing points', async () => {
    const frames = [];
    let count = 0;
    const context = vm.createContext({
        Promise, pendingOutletFocus:null,
        addOutletMarker:outlet=>{ if (++count === 81) throw new Error('marker failed'); return outlet; },
        outletClusterLayer:{addLayers(){}},
        window:{requestAnimationFrame:callback=>frames.push(callback)}
    });
    vm.runInContext(queueSource, context);
    const pending = context.queueOutletMarkers(Array.from({length:100},(_,id)=>({id})));
    frames.shift()();
    await assert.rejects(pending,/marker failed/);
});

test('initialization errors display a retry and do not leave a completed promise blocking future loads', async () => {
    const elements = {
        'map-outlet-count':{textContent:''},
        'branding-insights':{innerHTML:''},
        'map-outlet-error':{hidden:true,querySelector:()=>({textContent:''})}
    };
    let calls = 0, fetches = 0;
    const context = vm.createContext({
        Promise, AbortController, setTimeout:()=>1, clearTimeout(){},
        document:{getElementById:id=>elements[id]}, map:null, outletClusterLayer:null,
        setupMapMobileTabs(){},
        initMap:()=>{ if (++calls===1) throw new Error('dependency unavailable'); },
        fetch:async()=>{ fetches++; return {ok:true,json:async()=>[{has_front_photo:false}]}; },
        renderBrandingInsights(){}, console:{error(){}}
    });
    vm.runInContext(loadSource,context);
    await context.loadAllOutlets();
    assert.equal(fetches,0);
    assert.equal(elements['map-outlet-error'].hidden,false);
    await context.loadAllOutlets();
    assert.equal(fetches,1);
    assert.equal(elements['map-outlet-error'].hidden,true);
    assert.match(elements['map-outlet-count'].textContent,/1 outlet/);
});
