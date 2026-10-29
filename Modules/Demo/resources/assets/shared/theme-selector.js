/* Module-owned shared theme selector: palettes and storage are scoped per demo. */
(() => {
'use strict';
const root=document.querySelector('[data-demo-theme]');if(!root)return;
const themeKey=root.dataset.themeStorageKey, settings=document.getElementById(root.dataset.themeTarget);
if(!themeKey||!settings)return;
const palettes={
 original:['#6A1B9A','#4A148C','#8E24AA','#BA68C8','#F3E5F5','#FBF7FD','#2a0b3d','#3a1354','#d6c6e4'],
 ruby:['#C62828','#8E1B1B','#D84343','#E57373','#FCE8E8','#FFF8F8','#431619','#5B2328','#F4D6D6'],
 ocean:['#087E9B','#065E76','#139DB8','#65BED0','#E0F3F7','#F5FCFD','#103744','#155065','#D6F1F6'],
 emerald:['#16835D','#106344','#279A75','#71C5A7','#E0F5EC','#F5FCF8','#103D32','#185344','#D8F2E6'],
 indigo:['#4F46E5','#3730A3','#6861EC','#A5A0F3','#EBEAFE','#F8F8FF','#25234C','#353269','#E2E1FF']
};

const vars=['--chu-dao','--chu-dao-dam','--chu-dao-vua','--chu-dao-sang','--chu-dao-nhat','--chu-dao-mo','--sb','--sb2','--sbtext'];
function theme(name){if(!palettes[name])name='original';vars.forEach((v,i)=>document.documentElement.style.setProperty(v,palettes[name][i]));try{localStorage.setItem(themeKey,name)}catch(_){}document.querySelectorAll('[data-rental-theme]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.rentalTheme===name)));}
const panel=(title)=>{const p=document.createElement('div');p.className='card noprint';p.style.cssText='padding:18px;margin-bottom:16px';const h=document.createElement('h3');h.textContent=title;p.append(h);settings.prepend(p);return p};
const el=(tag,label,cls='btn')=>{const e=document.createElement(tag);if(tag==='button'){e.type='button';e.className=cls;}if(label)e.textContent=label;return e};
const themePanel=panel('Màu sắc giao diện');
themePanel.classList.add('rental-themes');
const themeHint=document.createElement('p');themeHint.className='muted';themeHint.textContent='Chọn một trong 5 giao diện. Lựa chọn được lưu trên trình duyệt.';themePanel.append(themeHint);
const themeRow=document.createElement('div');themeRow.className='rental-theme-options';
for(const [id,name] of Object.entries({original:'Màu gốc',ruby:'Ruby',ocean:'Ocean',emerald:'Emerald',indigo:'Indigo'})){
 const b=el('button');b.className='rental-theme-choice';b.dataset.rentalTheme=id;
 b.style.setProperty('--choice-color',palettes[id][0]);b.style.setProperty('--choice-dark',palettes[id][6]);
 const swatch=document.createElement('span');swatch.className='rental-theme-swatch';swatch.setAttribute('aria-hidden','true');
 const label=document.createElement('span');label.className='rental-theme-label';label.textContent=name;
 b.append(swatch,label);b.onclick=()=>theme(id);themeRow.append(b);
}
themePanel.append(themeRow);try{theme(localStorage.getItem(themeKey)||'original')}catch(_){theme('original')}

})();
