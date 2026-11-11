(() => {
 'use strict';
 const monthNames=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
 const tables=[...document.querySelectorAll('[data-transaction-table]')];
 if(!tables.length)return;
 const storageKey='surya-report-layout:'+location.pathname;
 let saved={};try{const parsed=JSON.parse(sessionStorage.getItem(storageKey)||'{}');if(parsed&&typeof parsed==='object'&&!Array.isArray(parsed))saved=parsed;}catch{}
 function enhance(table){
  const kind=table.dataset.transactionTable,card=table.closest('.ngrs-card');
  let state={mode:'monthly',range:Number(table.dataset.reportDefaultRange)||3,identities:false,...saved[kind]};
  if(!['monthly','gap','percent'].includes(state.mode))state.mode='monthly';
  if(![3,6,12].includes(state.range))state.range=3;
  state.identities=state.identities===true;
  let tools=card.querySelector('.report-table-tools');
  if(!tools){
   tools=document.createElement('div');tools.className='report-table-tools';
   const modes=document.createElement('div');modes.className='report-view-switch';modes.setAttribute('role','group');modes.setAttribute('aria-label','Tampilan '+(kind==='summary'?'rekap TAP':'detail outlet'));
   for(const [key,label]of[['monthly','Bulanan'],['gap','GAP MoM'],['percent','MoM %']]){
    const button=document.createElement('button');button.type='button';button.dataset.reportMode=key;button.textContent=label;button.addEventListener('click',()=>{state.mode=key;apply();});modes.append(button);
   }
   const right=document.createElement('div');right.className='report-table-options';
   const label=document.createElement('label');label.append('Periode ');const select=document.createElement('select');select.setAttribute('aria-label','Jumlah bulan '+kind);
   for(const [value,text]of[[3,'3 bulan terakhir'],[6,'6 bulan terakhir'],[12,'Setahun penuh']]){const option=document.createElement('option');option.value=value;option.textContent=text;select.append(option);}
   select.value=String(state.range);select.addEventListener('change',()=>{state.range=Number(select.value);apply();});label.append(select);right.append(label);
   if(kind==='detail'){const label=document.createElement('label');label.className='report-identity-toggle';const input=document.createElement('input');input.type='checkbox';input.checked=state.identities;input.addEventListener('change',()=>{state.identities=input.checked;apply();});label.append(input,'Semua identitas');right.append(label);}
   const status=document.createElement('span');status.className='report-range-label';status.setAttribute('aria-live','polite');right.append(status);
   tools.append(modes,right);card.prepend(tools);
  }
  function apply(){
   const end=Number(table.dataset.reportEndMonth)||12,start=Math.max(1,end-state.range+1),query=new URL(location.href).searchParams;
   const active=new Set([...query.keys()].filter(key=>/^(columns|exclude)\[/.test(key)).map(key=>key.match(/\[([^\]]+)/)?.[1]));if(query.get('sort'))active.add(query.get('sort'));
   tools.querySelectorAll('[data-report-mode]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.reportMode===state.mode)));
   const columnRow=table.tHead.querySelector('[data-report-columns]')||table.tHead.rows[0];
   const headers=[...columnRow.cells];
   const rows=[...table.rows].filter(row=>row.cells.length===headers.length);

   headers.forEach((header,index)=>{
    const filterKey=header.querySelector('[data-column]')?.dataset.column;
    const key=header.dataset.reportKey||filterKey||'';
    let visible=true,number=false,current=false;
    const month=key.match(/^m(\d+)$/),gap=key.match(/^gap_m(\d+)$/),percent=key.match(/^mom_pct_m(\d+)$/);
    if(month){number=true;current=Number(month[1])===end;visible=state.mode==='monthly'&&Number(month[1])>=start&&Number(month[1])<=end;}
    else if(gap||percent){number=true;const m=Number((gap||percent)[1]);visible=m>=start&&m<=end&&(state.mode===(gap?'gap':'percent')||(state.mode==='monthly'&&m===end));}
    else if(['compare','mom_compare'].includes(key)){number=true;visible=state.mode==='monthly'&&end-1>=start;}
    else if(key==='total'){number=true;visible=state.mode==='monthly';}
    else if(kind==='detail'&&['rs','nomor_rs','kecamatan','sf','pjp'].includes(key))visible=state.identities;
    if(active.has(filterKey||key))visible=true;
    for(const row of rows){const cell=row.cells[index];cell.hidden=!visible;cell.classList.toggle('report-numeric',number);cell.classList.toggle('report-current',current);cell.classList.toggle('report-category',key==='kategori_outlet');}
   });
   let first=0;
   for(const group of table.tHead.querySelectorAll('[data-report-group]')){
    const end=first+Number(group.dataset.reportGroup);
    const visible=headers.slice(first,end).filter(header=>!header.hidden).length;
    group.hidden=visible===0;
    group.colSpan=Math.max(1,visible);
    first=end;
   }
   tools.querySelector('.report-range-label').textContent=monthNames[start-1]+'–'+monthNames[end-1];
   saved[kind]=state;try{sessionStorage.setItem(storageKey,JSON.stringify(saved));}catch{}
   const name=table.querySelector('thead .ngrs-frozen-name'),tap=table.querySelector('thead .ngrs-frozen-tap');
   if(name&&tap){table.style.setProperty('--ngrs-name-width',name.getBoundingClientRect().width+'px');table.style.setProperty('--ngrs-tap-width',tap.getBoundingClientRect().width+'px');}
  }
  apply();return apply;
 }
 const refresh=tables.map(enhance);
 document.addEventListener('taskforce:updated',()=>refresh.forEach(apply=>apply()));

 const summary=document.querySelector('[data-report-summary-url]');
 if(summary){
  const status=summary.querySelector('[role="status"]');
  const retry=summary.querySelector('[data-report-summary-retry]');
  async function loadSummary(){
   summary.setAttribute('aria-busy','true');
   retry.hidden=true;
   status.textContent='Memuat rekap TAP…';
   try{
    const response=await fetch(summary.dataset.reportSummaryUrl);
    if(!response.ok)throw new Error('Rekap gagal dimuat');
    const doc=new DOMParser().parseFromString(await response.text(),'text/html');
    const table=doc.querySelector('[data-transaction-table="summary"]');
    const card=table?.closest('.ngrs-card');
    if(!card)throw new Error('Rekap gagal dimuat');
    summary.replaceChildren(card);
    refresh.push(enhance(table));
   }catch{
    status.textContent='Rekap TAP gagal dimuat.';
    retry.hidden=false;
   }finally{
    summary.removeAttribute('aria-busy');
   }
  }
  retry.addEventListener('click',loadSummary);
  loadSummary();
 }
})();
