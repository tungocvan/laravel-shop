<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Str;
use Modules\System\Models\Setting;

class AdminThemeProfileService
{
    public const DEFAULT_PROFILE = 'professional-indigo';
    private const CUSTOM_SETTING = 'admin_theme_profiles';
    private const ACTIVE_SETTING = 'admin_theme_profile';

    public function profiles(): array { return array_replace($this->builtIns(), $this->customProfiles()); }
    public function profile(string $name): array { return $this->profiles()[$name] ?? $this->builtIns()[self::DEFAULT_PROFILE]; }
    public function activeName(): string { $name=(string)Setting::getValue(self::ACTIVE_SETTING,self::DEFAULT_PROFILE); return array_key_exists($name,$this->profiles())?$name:self::DEFAULT_PROFILE; }
    public function setActive(string $name): void { if(!array_key_exists($name,$this->profiles()))$name=self::DEFAULT_PROFILE; Setting::setValue(self::ACTIVE_SETTING,$name,'admin_layout','text'); }
    public function apply(string $name,array $config): array { return array_replace_recursive($config,(array)($this->profile($name)['payload']??[])); }
    public function saveCustom(string $label,array $config): string { return $this->storeCustom($label,$this->extractPayload($config),'Theme tùy chỉnh được lưu từ Theme Editor.'); }
    public function duplicate(string $name): string { $profiles=$this->profiles(); abort_unless(isset($profiles[$name]),404); $source=$profiles[$name]; return $this->storeCustom('Bản sao '.$source['label'],(array)$source['payload'],'Bản sao của theme '.$source['label'].'.'); }
    public function deleteCustom(string $name): bool { $profiles=$this->customProfiles(); if(!isset($profiles[$name]))return false; unset($profiles[$name]); Setting::setValue(self::CUSTOM_SETTING,$profiles,'admin_layout','json'); if($this->activeName()===$name)$this->setActive(self::DEFAULT_PROFILE); return true; }

    private function storeCustom(string $label,array $payload,string $description): string
    {
        $label=trim($label); $label=$label!==''?mb_substr($label,0,80):'Theme tùy chỉnh'; $slug=Str::slug($label)?:'custom-theme'; if(array_key_exists($slug,$this->builtIns()))$slug.='-custom'; $profiles=$this->customProfiles(); $base=$slug;$counter=2; while(array_key_exists($slug,$profiles))$slug=$base.'-'.$counter++;
        $profiles[$slug]=['label'=>$label,'description'=>mb_substr($description,0,160),'built_in'=>false,'payload'=>$payload]; Setting::setValue(self::CUSTOM_SETTING,$profiles,'admin_layout','json'); $this->setActive($slug); return $slug;
    }

    public function extractPayload(array $config): array
    {
        return ['design'=>(array)data_get($config,'design',[]),'theme'=>['default'=>data_get($config,'theme.default','soft-light'),'dark_mode'=>'class','accent'=>data_get($config,'theme.accent','indigo')],'layout'=>['surface'=>(array)data_get($config,'layout.surface',[])],'sidebar'=>['presentation'=>['background'=>data_get($config,'sidebar.presentation.background','system')]],'header'=>['presentation'=>(array)data_get($config,'header.presentation',[])],'footer'=>['presentation'=>(array)data_get($config,'footer.presentation',[])]];
    }

    private function customProfiles(): array
    {
        $value=Setting::getValue(self::CUSTOM_SETTING,[]); if(is_string($value))$value=json_decode($value,true); if(!is_array($value))return [];
        return collect($value)->filter(fn($profile,$key)=>is_string($key)&&is_array($profile)&&isset($profile['payload']))->map(fn(array $profile)=>['label'=>mb_substr(trim((string)($profile['label']??'Theme tùy chỉnh')),0,80),'description'=>mb_substr(trim((string)($profile['description']??'Theme tùy chỉnh')),0,160),'built_in'=>false,'payload'=>(array)$profile['payload']])->all();
    }

    private function builtIns(): array
    {
        $baseDesign=[
            'typography'=>['font_family'=>'sans','body_size'=>'sm','page_title_size'=>'2xl','heading_weight'=>'semibold'],
            'sidebar_menu'=>[
                'item'=>['font_family'=>'inherit','font_size'=>'sm','font_weight'=>'medium','title_color'=>'slate-900','icon_color'=>'slate-400','icon_size'=>'20','item_height'=>'44'],
                'submenu'=>['font_family'=>'inherit','font_size'=>'13','font_weight'=>'normal','title_color'=>'slate-500','icon_color'=>'slate-400','indent'=>'28','item_height'=>'36'],
                'active'=>['title_color'=>'white','icon_color'=>'white','font_weight'=>'semibold'],
            ],
            'spacing'=>['tight'=>'2','control'=>'3','content'=>'4','section'=>'6'],'radius'=>['control'=>'lg','panel'=>'lg','overlay'=>'xl']
        ];
        $shell=fn(array $design,string $sidebarPalette,string $accent='indigo',string $sidebarBackground='system')=>['design'=>array_replace_recursive($baseDesign,$design),'theme'=>['default'=>$sidebarPalette,'dark_mode'=>'class','accent'=>$accent],'layout'=>['surface'=>['page_background'=>'system','content_surface'=>'transparent','border'=>'system','radius'=>'lg']],'sidebar'=>['presentation'=>['background'=>$sidebarBackground]],'header'=>['presentation'=>['mode'=>'balanced','padding_x'=>'6','action_gap'=>'2','background'=>'system','divider'=>'subtle','shadow'=>'subtle','backdrop_blur'=>true]],'footer'=>['presentation'=>['alignment'=>'split','background'=>'system','divider'=>'subtle','compact'=>true]]];
        return [
            self::DEFAULT_PROFILE=>['label'=>'Professional Indigo','description'=>'Mặc định khuyến nghị: trung tính, rõ hierarchy, accent Indigo vừa đủ.','built_in'=>true,'payload'=>$shell(['colors'=>['surface_base'=>'slate-50','surface_raised'=>'white','text_primary'=>'slate-900','text_secondary'=>'slate-700','text_muted'=>'slate-500','border_subtle'=>'slate-200','accent'=>'indigo-600','focus_ring'=>'indigo-500','success'=>'emerald-600','warning'=>'amber-500','danger'=>'rose-600','info'=>'sky-600','header_background'=>'white','footer_background'=>'white','page_background'=>'slate-50','content_background'=>'white','sidebar_header_background'=>'white','sidebar_navigation_background'=>'slate-50','sidebar_footer_background'=>'white']],'soft-light','indigo')],
            'corporate-blue'=>['label'=>'Corporate Blue','description'=>'Phong cách enterprise sáng, xanh dương tin cậy và tương phản cao.','built_in'=>true,'payload'=>$shell(['colors'=>['surface_base'=>'slate-50','surface_raised'=>'white','text_primary'=>'slate-900','text_secondary'=>'slate-700','text_muted'=>'slate-500','border_subtle'=>'slate-200','accent'=>'blue-600','focus_ring'=>'blue-500','success'=>'emerald-600','warning'=>'amber-500','danger'=>'rose-600','info'=>'sky-600','header_background'=>'blue-50','footer_background'=>'white','page_background'=>'slate-50','content_background'=>'white','sidebar_header_background'=>'blue-50','sidebar_navigation_background'=>'white','sidebar_footer_background'=>'blue-50']],'corporate-blue','blue')],
            'modern-dark'=>['label'=>'Modern Dark','description'=>'Dark admin hiện đại, surface Slate và accent Indigo dịu mắt.','built_in'=>true,'payload'=>$shell(['colors'=>['surface_base'=>'slate-950','surface_raised'=>'slate-900','text_primary'=>'slate-100','text_secondary'=>'slate-200','text_muted'=>'slate-400','border_subtle'=>'slate-700','accent'=>'indigo-400','focus_ring'=>'indigo-500','success'=>'emerald-500','warning'=>'amber-400','danger'=>'rose-500','info'=>'sky-500','header_background'=>'slate-900','footer_background'=>'slate-900','page_background'=>'slate-950','content_background'=>'slate-900','sidebar_header_background'=>'slate-950','sidebar_navigation_background'=>'slate-900','sidebar_footer_background'=>'slate-950'],'sidebar_menu'=>['item'=>['title_color'=>'slate-100','icon_color'=>'slate-400'],'submenu'=>['title_color'=>'slate-400'],'active'=>['title_color'=>'white','icon_color'=>'white']]],'modern-dark','indigo')],
            'warm-sunset'=>['label'=>'Warm Sunset','description'=>'Ấm hơn cho marketing/education nhưng vẫn giữ surface trung tính chuyên nghiệp.','built_in'=>true,'payload'=>$shell(['colors'=>['surface_base'=>'orange-50','surface_raised'=>'white','text_primary'=>'slate-900','text_secondary'=>'slate-700','text_muted'=>'slate-500','border_subtle'=>'orange-200','accent'=>'orange-600','focus_ring'=>'orange-500','success'=>'emerald-600','warning'=>'amber-500','danger'=>'rose-600','info'=>'sky-600','header_background'=>'orange-50','footer_background'=>'white','page_background'=>'orange-50','content_background'=>'white','sidebar_header_background'=>'orange-50','sidebar_navigation_background'=>'white','sidebar_footer_background'=>'orange-50']],'sunset-orange','amber')],
        ];
    }
}
