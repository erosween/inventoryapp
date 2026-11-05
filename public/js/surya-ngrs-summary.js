(() => {
 'use strict';
 const tabs=[...document.querySelectorAll('[data-ngrs-summary]')], panel=document.getElementById('ngrs-summary-panel');
 if(!panel)return;
 function select(mode){
  const average=mode==='average';
  const format=new Intl.NumberFormat('id-ID',{minimumFractionDigits:average?2:0,maximumFractionDigits:average?2:0});
  tabs.forEach(tab=>tab.setAttribute('aria-selected',String(tab.dataset.ngrsSummary===mode)));
  panel.setAttribute('aria-labelledby',`ngrs-${mode}-tab`);
  panel.querySelectorAll('[data-ngrs-qty]').forEach(cell=>{const days=Number(cell.dataset.ngrsDays);cell.textContent=days?format.format(Number(cell.dataset.ngrsQty)/(average?days:1)):'—';});
  panel.querySelector('[data-ngrs-summary-unit]').textContent=average?'TRX / HARI':'TRX';
  panel.querySelector('[data-ngrs-summary-total]').textContent=average?'Rata-rata / hari':'Total';
  document.querySelector('[data-ngrs-average-note]').hidden=!average;
  document.querySelector('[data-ngrs-summary-input]').value=mode;
  const url=new URL(location.href);url.searchParams.set('summary_view',mode);history.replaceState({},'',url);
 }
 tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>select(tab.dataset.ngrsSummary));tab.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();const next=tabs[event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:tabs.length-1))%tabs.length];next.focus();select(next.dataset.ngrsSummary);});});
})();
