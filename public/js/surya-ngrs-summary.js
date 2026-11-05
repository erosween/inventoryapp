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
   cell.textContent=days?format.format(recharge?value/1e9:average?Math.ceil(value):value):'—';
  });
  panel.querySelectorAll('[data-ngrs-gap]').forEach(cell=>{
   const days=Number(cell.dataset.currentDays),previousDays=Number(cell.dataset.previousDays);
   let text='—',direction='missing';
   if(days>0&&previousDays>0){
    const current=Number(recharge?cell.dataset.currentRecharge:cell.dataset.currentTrx),previous=Number(recharge?cell.dataset.previousRecharge:cell.dataset.previousTrx);
    const raw=recharge?current/(average?days:1)-previous/(average?previousDays:1):(average?Math.ceil(current/days)-Math.ceil(previous/previousDays):current-previous);
    const value=recharge?Math.sign(raw)*Math.round(Math.abs(raw)/1e6)/1000:raw;
    direction=value>0?'positive':value<0?'negative':'flat';text=(value>0?'▲ +':value<0?'▼ -':'')+format.format(Math.abs(value));
   }
   cell.classList.remove('is-positive','is-negative','is-flat','is-missing');cell.classList.add('is-'+direction);cell.textContent=text;
  });
  panel.querySelector('[data-ngrs-summary-unit]').textContent=recharge?(average?'RECH mio / HARI':'RECH (mio)'):(average?'TRX / HARI':'TRX');
  panel.querySelector('[data-ngrs-summary-total]').textContent=average?'Rata-rata / hari':'Total';
  document.querySelector('[data-ngrs-average-note]').hidden=!average;
  document.getElementById('ngrs-average-tab').textContent=recharge?'Average Recharge':'Average TRX';
  document.querySelector('[data-ngrs-summary-input]').value=mode;document.querySelector('[data-ngrs-metric-input]').value=metric;
  const url=new URL(location.href);url.searchParams.set('summary_view',mode);url.searchParams.set('summary_metric',metric);history.replaceState({},'',url);
 }
 const mode=()=>document.querySelector('[data-ngrs-summary-input]').value, metric=()=>document.querySelector('[data-ngrs-metric-input]').value;
 tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>select(tab.dataset.ngrsSummary,metric()));tab.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();const next=tabs[event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:tabs.length-1))%tabs.length];next.focus();select(next.dataset.ngrsSummary,metric());});});
 metrics.forEach(tab=>tab.addEventListener('click',()=>select(mode(),tab.dataset.ngrsMetric)));
})();
