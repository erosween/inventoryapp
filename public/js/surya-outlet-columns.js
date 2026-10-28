(() => {
 'use strict';
 let pending;
 window.SuryaTaskforceNavigate=async(target,push=true)=>{
  pending?.abort();const ctrl=new AbortController();pending=ctrl;
  const table=document.querySelector('.detail-table'),wrap=table.closest('.table-wrap'),footer=document.querySelector('.table-footer');
  const state={x:scrollX,y:scrollY,left:wrap.scrollLeft,top:wrap.scrollTop,height:wrap.getBoundingClientRect().height};
  table.setAttribute('aria-busy','true');
  try{
   const response=await fetch(target,{signal:ctrl.signal,headers:{'X-Requested-With':'XMLHttpRequest'}});
   if(!response.ok)throw Error('Data gagal dimuat. Coba kembali.');
   const doc=new DOMParser().parseFromString(await response.text(),'text/html'),next=doc.querySelector('.detail-table');
   if(!next)throw Error('Sesi berakhir. Muat ulang halaman untuk masuk kembali.');
   table.innerHTML=next.innerHTML;footer.innerHTML=doc.querySelector('.table-footer').innerHTML;
   document.querySelector('#tf-column-menu').replaceWith(doc.querySelector('#tf-column-menu'));
   const options=document.querySelector('[data-column-options]');if(options)options.innerHTML=doc.querySelector('[data-column-options]').innerHTML;
   const link=document.querySelector('.filters a');link.href=doc.querySelector('.filters a').href;
   if(push)history.pushState({},'',target);
   wrap.style.minHeight=state.height+'px';
   const restore=()=>{wrap.scrollLeft=state.left;wrap.scrollTop=state.top;window.scrollTo(state.x,state.y);};restore();requestAnimationFrame(restore);
   document.dispatchEvent(new Event('taskforce:updated'));
  }catch(error){if(error.name!=='AbortError'){const menu=document.querySelector('#tf-column-menu');menu.hidden=false;menu.querySelector('[data-filter-message]').textContent=error.message;}}
  finally{if(pending===ctrl)table.removeAttribute('aria-busy');}
 };
 document.addEventListener('click',event=>{const link=event.target.closest('.table-footer .page-link');if(!link)return;event.preventDefault();if(!link.classList.contains('disabled'))window.SuryaTaskforceNavigate(link.href);});
 document.addEventListener('submit',event=>{if(!event.target.matches('.page-size'))return;event.preventDefault();const url=new URL(location.href);url.searchParams.set('per_page',new FormData(event.target).get('per_page'));url.searchParams.delete('page');window.SuryaTaskforceNavigate(url.toString());});
 document.addEventListener('change',event=>{if(!event.target.matches('.page-size select'))return;event.stopImmediatePropagation();const url=new URL(location.href);url.searchParams.set('per_page',event.target.value);url.searchParams.delete('page');window.SuryaTaskforceNavigate(url.toString());},true);
 document.querySelector('.page-size select')?.removeAttribute('onchange');
 window.addEventListener('popstate',()=>window.SuryaTaskforceNavigate(location.href,false));
})();
