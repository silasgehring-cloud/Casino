document.addEventListener('DOMContentLoaded',function(){
  const coinForm=document.getElementById('fc-coinflip-form');
  if(coinForm){
    coinForm.addEventListener('submit',function(){
      const anim=document.getElementById('flip-anim');
      if(anim) anim.classList.remove('fc-hide');
    });
  }
  const slotForm=document.getElementById('fc-slot-form');
  if(slotForm){
    slotForm.addEventListener('submit',function(){
      const anim=document.getElementById('slot-anim');
      if(anim) anim.classList.remove('fc-hide');
    });
  }
  const rouletteForm=document.getElementById('fc-roulette-form');
  if(rouletteForm){
    rouletteForm.addEventListener('submit',function(){
      const anim=document.getElementById('roulette-anim');
      if(anim) anim.classList.remove('fc-hide');
    });
  }
  const blackjackForm=document.getElementById('fc-blackjack-form');
  if(blackjackForm){
    blackjackForm.addEventListener('submit',function(){
      const anim=document.getElementById('blackjack-anim');
      if(anim) anim.classList.remove('fc-hide');
    });
  }
  const fcTitle=document.getElementById('fc-auth-title');
  const fcToggleText=document.getElementById('fc-auth-toggle-text');
  const fcToggleLink=document.getElementById('fc-auth-toggle-link');
  const confirmField=document.querySelector('input[name="confirm"]');
  const actionField=document.getElementById('fc_auth_action');
  if(fcToggleLink&&fcTitle&&fcToggleText&&confirmField&&actionField){
    let isLogin=true;
    fcToggleLink.addEventListener('click',function(e){
      e.preventDefault();
      isLogin=!isLogin;
      fcTitle.textContent=isLogin?fcTitle.dataset.login:fcTitle.dataset.register;
      fcToggleText.textContent=isLogin?fcToggleText.dataset.login:fcToggleText.dataset.register;
      fcToggleLink.textContent=isLogin?fcToggleLink.dataset.login:fcToggleLink.dataset.register;
      confirmField.classList.toggle('fc-auth-confirm',isLogin);
      confirmField.required=!isLogin;
      actionField.value=isLogin?'login':'register';
    });
  }
});
