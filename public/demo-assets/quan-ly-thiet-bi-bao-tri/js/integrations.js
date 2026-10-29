/* Equipment maintenance: independent theme settings; business data remains in original app.js. */
(() => {
  'use strict';
  const KEY = 'equipment_maintenance_theme_v1';
  const themes = {
    original: ['Màu gốc', '#2E7D32', '#1B5E20', '#66BB6A', '#E8F5E9', '#123F22', '#1C5130', '#082714', '#C8E3CB', '#8DB894'],
    ruby: ['Ruby', '#C62828', '#8E1B22', '#EF5350', '#FFEBEE', '#39161C', '#55232A', '#250E13', '#F6CED1', '#D49A9F'],
    ocean: ['Ocean', '#087E9B', '#07556E', '#31B6CE', '#E2F6FA', '#102C3A', '#19465A', '#09202B', '#C7EBF1', '#91BFCB'],
    emerald: ['Emerald', '#16835D', '#0F6247', '#34C78C', '#E4F8EF', '#103327', '#1B513C', '#08231A', '#C7EADB', '#90C7AF'],
    indigo: ['Indigo', '#4F46E5', '#3730A3', '#818CF8', '#EEF2FF', '#201C42', '#302B62', '#15112F', '#E0DDFC', '#B2ACDD']
  };
  const names = ['--chu-dao','--cd-dam','--cd-sang','--cd-nhat','--cd-toi','--cd-toi2','--cd-toi3','--sbtext','--sbmuted'];
  function apply(id) {
    if (!Object.hasOwn(themes,id)) id='original';
    const t=themes[id];
    names.forEach((name,i)=>document.documentElement.style.setProperty(name,t[i+1]));
    document.documentElement.style.setProperty('--cd-bong',id==='original'?'rgba(46,125,50,.30)':t[1]+'40');
    document.documentElement.dataset.equipmentTheme=id;
    try {localStorage.setItem(KEY,id)} catch (_) {}
    document.querySelectorAll('[data-equipment-theme]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.equipmentTheme===id)));
  }
  function mount() {
    const settings=document.querySelector('#page-settings .set-grid');
    if(!settings || settings.querySelector('.equipment-theme-panel')) return;
    const card=document.createElement('section');
    card.className='card equipment-theme-panel';
    const title=document.createElement('h4');title.textContent='Màu sắc giao diện';
    const note=document.createElement('p');note.textContent='Chọn 1 trong 5 themes. Lựa chọn được lưu riêng trên trình duyệt.';
    const options=document.createElement('div');options.className='equipment-theme-options';
    options.setAttribute('role','group');options.setAttribute('aria-label','Chọn màu giao diện');
    Object.entries(themes).forEach(([id,t])=>{
      const b=document.createElement('button');b.type='button';b.className='equipment-theme-choice';
      b.dataset.equipmentTheme=id;b.style.setProperty('--preview',t[1]);b.style.setProperty('--preview-dark',t[5]);
      const swatch=document.createElement('span');swatch.className='equipment-theme-swatch';swatch.setAttribute('aria-hidden','true');
      const label=document.createElement('span');label.textContent=t[0];
      b.append(swatch,label);b.addEventListener('click',()=>apply(id));options.append(b);
    });
    card.append(title,note,options);settings.append(card);
    apply(document.documentElement.dataset.equipmentTheme||'original');
  }
  let initial='original';try {initial=localStorage.getItem(KEY)||initial}catch(_){}
  apply(initial);
  if(typeof APP!=='undefined' && APP.pages?.settings){
    const original=APP.pages.settings[2];
    APP.pages.settings[2]=function(){original();mount()};
    if(typeof CUR!=='undefined'&&CUR==='settings')mount();
  }
})();
