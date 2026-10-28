const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname,'../../public/js/surya-taskforce-columns.js'),'utf8');

function setup(touch) {
    let document;
    class Element extends EventTarget {
        constructor() { super(); this.style={}; this.dataset={}; this.hidden=false; this.isConnected=true; this.value=''; this.focusCount=0; this.blurCount=0; }
        setAttribute(name,value) { this[name]=value; }
        replaceChildren() {}
        focus() { this.focusCount++; document.activeElement=this; }
        blur() { this.blurCount++; document.activeElement=null; }
        contains(node) { return node===this || (this===menu && Object.values(parts).includes(node)); }
        getBoundingClientRect() { return {left:700,right:724,bottom:250}; }
        closest() { return {getBoundingClientRect:()=>({left:700})}; }
    }
    const menu=new Element(); menu.hidden=true;
    menu.dataset={selection:'{}',excluded:'{}',valuesUrl:'https://surya.test/filter-values'};
    const parts={};
    for(const key of ['values','search','message','apply','more','all','none','reset','close']) parts[key]=new Element();
    menu.querySelector=selector=>parts[selector.match(/data-filter-([^\]]+)/)?.[1]] || new Element();
    menu.querySelectorAll=()=>[];
    const trigger=new Element();trigger.dataset={column:'0',label:'ID Outlet'};
    const title=new Element(), table=new Element();
    document=new EventTarget();document.activeElement=null;
    document.getElementById=id=>id==='tf-column-menu'?menu:title;
    document.querySelectorAll=()=>[trigger];document.querySelector=()=>table;
    const window=new EventTarget();window.matchMedia=()=>({matches:touch});
    const viewport=new EventTarget();Object.assign(viewport,{width:touch?390:1024,height:touch?844:768,offsetLeft:0,offsetTop:0});
    window.visualViewport=viewport;
    vm.runInNewContext(source,{document,window,navigator:{maxTouchPoints:touch?5:0},innerWidth:1024,innerHeight:768,
        AbortController,URL,Set,JSON,clearTimeout,setTimeout,location:{href:'https://surya.test/taskforce',search:''},fetch:()=>new Promise(()=>{})});
    return {menu,parts,trigger,window,viewport,document,table};
}

test('touch filter opens without automatically summoning the keyboard',()=>{
    const ui=setup(true);ui.trigger.dispatchEvent(new Event('click'));
    assert.equal(ui.menu.hidden,false);assert.equal(ui.parts.search.focusCount,0);
    assert.equal(ui.trigger['aria-expanded'],'true');
    assert.ok(parseFloat(ui.menu.style.width)<=ui.viewport.width-16);
});

test('keyboard resize keeps menu and search text, fitting the visible viewport',()=>{
    const ui=setup(true);ui.trigger.dispatchEvent(new Event('click'));
    ui.parts.search.value='DUMAI';ui.parts.search.focus();
    Object.assign(ui.viewport,{height:320,offsetTop:90});
    ui.window.dispatchEvent(new Event('resize'));ui.viewport.dispatchEvent(new Event('resize'));
    ui.viewport.dispatchEvent(new Event('scroll'));
    assert.equal(ui.menu.hidden,false);assert.equal(ui.parts.search.value,'DUMAI');
    assert.equal(ui.parts.search.focusCount,1);
    assert.equal(parseFloat(ui.menu.style.maxHeight),304);
    assert.ok(parseFloat(ui.menu.style.top)>=98);
    assert.ok(parseFloat(ui.menu.style.top)+parseFloat(ui.menu.style.maxHeight)<=410);
    Object.assign(ui.viewport,{width:844,height:270,offsetTop:0});
    ui.window.dispatchEvent(new Event('resize'));
    assert.equal(ui.menu.hidden,false);assert.ok(parseFloat(ui.menu.style.maxHeight)<=254);
});

test('explicit close blurs mobile search and desktop still offers autofocus',()=>{
    const ui=setup(true);ui.trigger.dispatchEvent(new Event('click'));ui.parts.search.focus();
    ui.parts.close.dispatchEvent(new Event('click'));
    assert.equal(ui.menu.hidden,true);assert.equal(ui.parts.search.blurCount,1);
    const desktop=setup(false);desktop.trigger.dispatchEvent(new Event('click'));
    assert.equal(desktop.parts.search.focusCount,1);
    desktop.window.dispatchEvent(new Event('resize'));assert.equal(desktop.menu.hidden,false);
});
