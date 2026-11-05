(() => {
 'use strict';
 const tabs=[...document.querySelectorAll('[data-ngrs-summary]')],metrics=[...document.querySelectorAll('[data-ngrs-metric]')],panel=document.getElementById('ngrs-summary-panel');
 if(!panel)return;
 function select(mode,metric){
  const average=mode==='average',recharge=metric==='rech';
  const format=new Intl.NumberFormat(recharge?'en-US':'id-ID',{minimumFractionDigits:recharge?3:0,maximumFractionDigits:recharge?3:0});
  tabs.forEach(tab=>tab.setAttribute('aria-selected',String(tab.dataset.ngrsSummary===mode)));
  metrics.forEach(tab=>tab.setAttribute('aria-pressed',String(tab.dataset.ngrsMetric===metric)));
  panel.setAttribute('aria-labelledby',`ngrs-${mode}-tab`);
  panel.querySelectorAll('[data-ngrs-qty]').forEach(cell=>{
   const days=Number(cell.dataset.ngrsDays),raw=Number(recharge?cell.dataset.ngrsRecharge:cell.dataset.ngrsQty),value=raw/(average?days:1);
   cell.textContent=days?format.format(recharge?value/1e9:average?Math.ceil(value):value)+(recharge?' mio':''):'—';
  });
  panel.querySelector('[data-ngrs-summary-unit]').textContent=recharge?(average?'RECH mio / HARI':'RECH (mio)'):(average?'TRX / HARI':'TRX');
  panel.querySelector('[data-ngrs-summary-total]').textContent=average?'Rata-rata / hari':'Total';
  document.querySelector('[data-ngrs-average-note]').hidden=!average;
  document.querySelector('[data-ngrs-recharge-note]').hidden=!recharge;
  document.getElementById('ngrs-average-tab').textContent=recharge?'Average Recharge':'Average TRX';
  document.querySelector('[data-ngrs-summary-input]').value=mode;document.querySelector('[data-ngrs-metric-input]').value=metric;
  const url=new URL(location.href);url.searchParams.set('summary_view',mode);url.searchParams.set('summary_metric',metric);history.replaceState({},'',url);
 }
 const mode=()=>document.querySelector('[data-ngrs-summary-input]').value, metric=()=>document.querySelector('[data-ngrs-metric-input]').value;
 tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>select(tab.dataset.ngrsSummary,metric()));tab.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();const next=tabs[event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:tabs.length-1))%tabs.length];next.focus();select(next.dataset.ngrsSummary,metric());});});
 metrics.forEach(tab=>tab.addEventListener('click',()=>select(mode(),tab.dataset.ngrsMetric)));
})();
