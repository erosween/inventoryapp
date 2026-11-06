(() => {
 'use strict';
 const input=document.querySelector('[data-transaction-date]');
 if(input&&typeof flatpickr==='function')flatpickr(input,{locale:flatpickr.l10ns.id,altInput:true,altFormat:'d M Y',dateFormat:'Y-m-d',minDate:input.min,maxDate:input.max,disableMobile:true});
})();
