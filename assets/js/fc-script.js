document.addEventListener('DOMContentLoaded',function(){
  function attachGameAnimation(formId,animId){
    const form=document.getElementById(formId);
    if(form){
      form.addEventListener('submit',function(e){
        const anim=document.getElementById(animId);
        if(anim) anim.classList.remove('fc-hide');
        e.preventDefault();
        setTimeout(()=>form.submit(),1200);
      });
    }
  }
  attachGameAnimation('fc-coinflip-form','flip-anim');
  attachGameAnimation('fc-slot-form','slot-anim');
  attachGameAnimation('fc-roulette-form','roulette-anim');
  attachGameAnimation('fc-blackjack-form','blackjack-anim');
  const fcTitle=document.getElementById('fc-auth-title');
  const fcToggleText=document.getElementById('fc-auth-toggle-text');
  const fcToggleLink=document.getElementById('fc-auth-toggle-link');
  const confirmField=document.querySelector('input[name="confirm"]');
  const actionField=document.getElementById('fc_auth_action');
  const authWrapper=document.querySelector('.fc-auth-wrapper');
  if(fcToggleLink&&fcTitle&&fcToggleText&&confirmField&&actionField&&authWrapper){
    let isLogin=true;
    fcToggleLink.addEventListener('click',function(e){
      e.preventDefault();
      isLogin=!isLogin;
      authWrapper.classList.add('fc-fade');
      setTimeout(()=>authWrapper.classList.remove('fc-fade'),300);
      fcTitle.textContent=isLogin?fcTitle.dataset.login:fcTitle.dataset.register;
      fcToggleText.textContent=isLogin?fcToggleText.dataset.login:fcToggleText.dataset.register;
      fcToggleLink.textContent=isLogin?fcToggleLink.dataset.login:fcToggleLink.dataset.register;
      confirmField.classList.toggle('fc-auth-confirm',isLogin);
      confirmField.required=!isLogin;
      actionField.value=isLogin?'login':'register';
    });
  }
});
