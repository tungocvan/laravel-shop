/* Product Costing themes. Dataset endpoints remain available for compatibility, but have no duplicate settings UI. */
(() => {
  'use strict';
  const key='product_costing_theme_v1';
  const themes={
    original:{label:'Màu gốc',color:'#EF6C00',dark:'#2B1A0E'},
    ruby:{label:'Ruby',color:'#C62828',dark:'#351319'},
    ocean:{label:'Ocean',color:'#087E9B',dark:'#102C3A'},
    emerald:{label:'Emerald',color:'#16835D',dark:'#103327'},
    indigo:{label:'Indigo',color:'#4F46E5',dark:'#201C42'}
  };
  function applyTheme(theme){
    if(!Object.hasOwn(themes,theme))theme='original';
    document.documentElement.dataset.costTheme=theme;
    try{localStorage.setItem(key,theme)}catch(_){}
    document.querySelectorAll('[data-cost-theme]').forEach(button=>{
      const selected=button.dataset.costTheme===theme;
      button.setAttribute('aria-pressed',String(selected));
      button.setAttribute('aria-label',themes[button.dataset.costTheme].label+(selected?' (đang chọn)':''));
    });
  }
  let initial='original';
  try{initial=localStorage.getItem(key)||initial}catch(_){}
  applyTheme(initial);
  function mount(){
    const settings=document.getElementById('page-settings');
    if(!settings)return;
    const grid=settings.querySelector('.set-grid');
    if(!grid||grid.querySelector('.cost-themes'))return;
    const card=document.createElement('section');
    card.className='card cost-themes';
    const heading=document.createElement('h4');
    heading.textContent='Màu sắc giao diện';
    const description=document.createElement('p');
    description.className='muted';
    description.textContent='Chọn một trong 5 giao diện. Lựa chọn được lưu trên trình duyệt.';
    const options=document.createElement('div');
    options.className='cost-theme-options';
    options.setAttribute('role','group');
    options.setAttribute('aria-label','Màu sắc giao diện');
    for(const [id,theme] of Object.entries(themes)){
      const button=document.createElement('button');
      button.type='button';
      button.className='cost-theme-choice';
      button.dataset.costTheme=id;
      button.style.setProperty('--choice-color',theme.color);
      button.style.setProperty('--choice-dark',theme.dark);
      const swatch=document.createElement('span');
      swatch.className='cost-theme-swatch';
      swatch.setAttribute('aria-hidden','true');
      const label=document.createElement('span');
      label.className='cost-theme-label';
      label.textContent=theme.label;
      button.append(swatch,label);
      button.addEventListener('click',()=>applyTheme(id));
      options.append(button);
    }
    card.append(heading,description,options);
    grid.append(card);
    applyTheme(document.documentElement.dataset.costTheme||'original');
  }
  if(typeof APP!=='undefined'&&APP.pages?.settings){
    const originalRender=APP.pages.settings[2];
    APP.pages.settings[2]=function(){originalRender();mount()};
    if(typeof CUR!=='undefined'&&CUR==='settings')mount();
  }
})();
