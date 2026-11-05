(() => {
 'use strict';
 let pending;
 window.SuryaTaskforceNavigate=async(target,push=true)=>{
  pending?.abort();const ctrl=new AbortController();pending=ctrl;
  const table=document.querySelector('.ngrs-detail'),wrap=table.closest('.ngrs-scroll'),footer=document.querySelector('.ngrs-footer');
  const state={x:scrollX,y:scrollY,left:wrap.scrollLeft,top:wrap.scrollTop};
  table.setAttribute('aria-busy','true');
  try{
   const response=await fetch(target,{signal:ctrl.signal,headers:{'X-Requested-With':'XMLHttpRequest'}});
   if(!response.ok)throw Error('Data gagal dimuat. Coba kembali.');
   const doc=new DOMParser().parseFromString(await response.text(),'text/html'),next=doc.querySelector('.ngrs-detail');
   if(!next)throw Error('Sesi berakhir. Muat ulang halaman untuk masuk kembali.');
   table.innerHTML=next.innerHTML;footer.innerHTML=doc.querySelector('.ngrs-footer').innerHTML;
   document.querySelector('#tf-column-menu').replaceWith(doc.querySelector('#tf-column-menu'));
   document.querySelector('[data-column-options]').innerHTML=doc.querySelector('[data-column-options]').innerHTML;
   document.querySelector('[data-ngrs-download]').href=doc.querySelector('[data-ngrs-download]').href;
   if(push)history.pushState({},'',target);
   const restore=()=>{wrap.scrollLeft=state.left;wrap.scrollTop=state.top;window.scrollTo(state.x,state.y);};restore();requestAnimationFrame(restore);
   document.dispatchEvent(new Event('taskforce:updated'));
  }catch(error){if(error.name!=='AbortError'){const menu=document.querySelector('#tf-column-menu');menu.hidden=false;menu.querySelector('[data-filter-message]').textContent=error.message;}}
  finally{if(pending===ctrl)table.removeAttribute('aria-busy');}
 };
 document.addEventListener('click',event=>{const link=event.target.closest('.ngrs-footer a');if(!link)return;event.preventDefault();window.SuryaTaskforceNavigate(link.href);});
 window.addEventListener('popstate',()=>window.SuryaTaskforceNavigate(location.href,false));
})();
