/* Rental room demo integrations: isolated theme, sidebar editor, JSON backups and private snapshots. */
(() => {
'use strict';
const slug='quan-ly-phong-tro';
const base='/demo/'+slug, csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
const notify=(msg)=>typeof toast==='function'?toast(msg):alert(msg);
const settings=document.getElementById('page-settings'),nav=document.getElementById('nav');
if(!settings||!nav)return;
const panel=(title)=>{const p=document.createElement('div');p.className='card noprint';p.style.cssText='padding:18px;margin-bottom:16px';const h=document.createElement('h3');h.textContent=title;p.append(h);settings.prepend(p);return p};
const el=(tag,label,cls='btn')=>{const e=document.createElement(tag);if(tag==='button'){e.type='button';e.className=cls;}if(label)e.textContent=label;return e};
const row=()=>{const d=document.createElement('div');d.style.cssText='display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:12px';return d};
const input=(value,placeholder)=>{const e=el('input');e.className='inp';e.type='text';e.value=value;e.placeholder=placeholder;e.maxLength=100;return e};
async function api(url,method='GET',body=null){
 const code=window.prompt('Nhập mã quản trị dữ liệu mẫu trên server:');
 if(code===null)return null;
 if(!code.trim())throw Error('Chưa nhập mã quản trị.');
 const response=await fetch(url,{
  method,credentials:'same-origin',cache:'no-store',
  headers:{'Accept':'application/json','X-Demo-Backup-Code':code,'X-CSRF-TOKEN':csrf,...(body?{'Content-Type':'application/json'}:{})},
  ...(body?{body:JSON.stringify(body)}:{})
 });
 if(!response.ok){const errors={403:'Mã quản trị không hợp lệ.',503:'Chưa cấu hình mã quản trị server.',419:'Phiên làm việc hết hạn.',422:'Dữ liệu gửi lên không hợp lệ.',409:'Thư viện server đã đạt giới hạn.',413:'Dữ liệu vượt giới hạn dung lượng.'};throw Error(errors[response.status]||'Yêu cầu server thất bại (HTTP '+response.status+').')}
 return response.json();
}

const dataPanel=panel('Sao lưu JSON và dữ liệu mẫu trên server'),dataRow=row(),backupButton=el('button','Tải bản sao lưu JSON'),restoreInput=el('input');restoreInput.type='file';restoreInput.accept='.json,application/json';restoreInput.className='inp';const datasetName=input('','Tên bản dữ liệu'),saveData=el('button','Lưu dữ liệu lên server'),listData=el('button','Danh sách server'),selectData=el('select'),restoreData=el('button','Khôi phục server');selectData.className='inp';
backupButton.onclick=()=>{const blob=new Blob([JSON.stringify(S,null,2)],{type:'application/json'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='quan-ly-phong-tro-'+new Date().toISOString().slice(0,10)+'.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000)};
window.saveToFile=backupButton.onclick;
restoreInput.onchange=async()=>{const f=restoreInput.files?.[0];if(!f)return;try{if(f.size>10485760)throw Error('File vượt 10MB');const d=JSON.parse(await f.text());if(!valid(d))throw Error('JSON không đúng cấu trúc');if(!confirm('Khôi phục sẽ thay dữ liệu hiện tại trong trình duyệt. Tiếp tục?'))return;S=norm(d);save();rerender();notify('Đã khôi phục')}catch(e){notify(e.message)}finally{restoreInput.value=''}};
saveData.onclick=async()=>{try{if(!datasetName.value.trim()||!confirm('Lưu bản dữ liệu server?'))return;const result=await api(base+'/datasets','POST',{name:datasetName.value.trim(),data:S});if(result)notify('Đã lưu dữ liệu server')}catch(e){notify(e.message)}};
listData.onclick=async()=>{try{const r=await api(base+'/datasets');if(!r)return;selectData.replaceChildren(new Option('Chọn bản dữ liệu',''));for(const d of r.datasets||[])selectData.add(new Option(d.name+' · '+d.savedAt,d.id))}catch(e){notify(e.message)}};
restoreData.onclick=async()=>{try{if(!selectData.value||!confirm('Khôi phục bản server và thay dữ liệu trình duyệt?'))return;const r=await api(base+'/datasets/'+encodeURIComponent(selectData.value));if(!r||!valid(r.data))throw Error('Dữ liệu server không hợp lệ');S=norm(r.data);save();rerender();notify('Đã khôi phục server')}catch(e){notify(e.message)}};
dataRow.append(backupButton,restoreInput,datasetName,saveData,listData,selectData,restoreData);dataPanel.append(dataRow);
})();