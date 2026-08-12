(()=>{
 const root=document.getElementById('sessionWarning');if(!root)return;
 const ttl=Number(root.dataset.ttl||1800)*1000,warningMs=Number(root.dataset.warning||60)*1000;
 const csrf=root.dataset.csrf||'',base=root.dataset.base||'';let deadline=Date.now()+ttl,lastKeepalive=Date.now(),visible=false,timer;
 const display=document.getElementById('sessionCountdown'),continueButton=document.getElementById('sessionContinue'),expireForm=document.getElementById('sessionExpireForm');
 const format=ms=>{const total=Math.max(0,Math.ceil(ms/1000));return `${String(Math.floor(total/60)).padStart(2,'0')}:${String(total%60).padStart(2,'0')}`};
 const renew=async manual=>{try{const body=new URLSearchParams({manual:manual?'1':'0'});const response=await fetch(`${base}/sesion/renovar`,{method:'POST',headers:{'X-CSRF-Token':csrf,'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body,credentials:'same-origin'});if(!response.ok)throw new Error();deadline=Date.now()+ttl;lastKeepalive=Date.now();visible=false;root.hidden=true}catch(_){window.location.assign(`${base}/login?expired=1`)}};
 const activity=()=>{deadline=Date.now()+ttl;if(visible){visible=false;root.hidden=true}if(Date.now()-lastKeepalive>300000)renew(false)};
 ['pointerdown','keydown','scroll','touchstart'].forEach(event=>addEventListener(event,activity,{passive:true}));
 const tick=()=>{const remaining=deadline-Date.now();if(remaining<=0){clearInterval(timer);expireForm?.submit();return}if(remaining<=warningMs){visible=true;root.hidden=false;if(display)display.textContent=format(remaining)}};
 continueButton?.addEventListener('click',()=>renew(true));timer=setInterval(tick,1000);tick();
})();
