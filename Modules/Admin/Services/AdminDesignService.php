<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminDesignService
{
    private AdminLayoutManager $layoutManager;

    private const COLOR_VALUES = [
        'white'=>'#ffffff','slate-50'=>'#f8fafc','slate-100'=>'#f1f5f9','slate-200'=>'#e2e8f0','slate-400'=>'#94a3b8','slate-500'=>'#64748b','slate-700'=>'#334155','slate-900'=>'#0f172a','slate-950'=>'#020617',
        'indigo-50'=>'#eef2ff','indigo-100'=>'#e0e7ff','indigo-400'=>'#818cf8','indigo-500'=>'#6366f1','indigo-600'=>'#4f46e5','blue-50'=>'#eff6ff','blue-100'=>'#dbeafe','blue-500'=>'#3b82f6','blue-600'=>'#2563eb',
        'orange-50'=>'#fff7ed','orange-100'=>'#ffedd5','orange-200'=>'#fed7aa','orange-500'=>'#f97316','orange-600'=>'#ea580c','emerald-50'=>'#ecfdf5','emerald-100'=>'#d1fae5','emerald-500'=>'#10b981','emerald-600'=>'#059669',
        'amber-50'=>'#fffbeb','amber-100'=>'#fef3c7','amber-400'=>'#fbbf24','amber-500'=>'#f59e0b','rose-50'=>'#fff1f2','rose-100'=>'#ffe4e6','rose-500'=>'#f43f5e','rose-600'=>'#e11d48','sky-50'=>'#f0f9ff','sky-100'=>'#e0f2fe','sky-500'=>'#0ea5e9','sky-600'=>'#0284c7',
    ];
    private const SURFACE_COLOR_KEYS=['white','slate-50','slate-100','slate-200','slate-900','slate-950','indigo-50','indigo-100','blue-50','blue-100','orange-50','orange-100','emerald-50','emerald-100','amber-50','amber-100','rose-50','rose-100','sky-50','sky-100'];
    private const FONT_FAMILIES=[
        'sans'=>'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'arial'=>'Arial, Helvetica, sans-serif','verdana'=>'Verdana, Geneva, sans-serif','trebuchet'=>'"Trebuchet MS", Arial, sans-serif','georgia'=>'Georgia, "Times New Roman", serif','times'=>'"Times New Roman", Times, serif','mono'=>'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace',
    ];
    private const FONT_LABELS=['sans'=>'System UI (khuyên dùng)','arial'=>'Arial','verdana'=>'Verdana','trebuchet'=>'Trebuchet MS','georgia'=>'Georgia','times'=>'Times New Roman','mono'=>'Monospace'];
    private const MENU_FONT_FAMILIES=['inherit'=>'inherit'] + self::FONT_FAMILIES;
    private const FONT_SIZES=['xs'=>'0.75rem','sm'=>'0.875rem','base'=>'1rem','lg'=>'1.125rem','2xl'=>'1.5rem'];
    private const MENU_FONT_SIZES=['12'=>'0.75rem','13'=>'0.8125rem','sm'=>'0.875rem','15'=>'0.9375rem','base'=>'1rem'];
    private const FONT_WEIGHTS=['normal'=>'400','medium'=>'500','semibold'=>'600','bold'=>'700'];
    private const SPACING_VALUES=['1'=>'0.25rem','2'=>'0.5rem','3'=>'0.75rem','4'=>'1rem','6'=>'1.5rem','8'=>'2rem'];
    private const RADIUS_VALUES=['sm'=>'0.25rem','md'=>'0.375rem','lg'=>'0.5rem','xl'=>'0.75rem'];
    private const BORDER_WIDTHS=['0'=>'0px','1'=>'1px','2'=>'2px','3'=>'3px'];
    private const BORDER_STYLES=['solid','dashed','dotted','double'];

    public function __construct(?AdminLayoutManager $layoutManager=null){$this->layoutManager=$layoutManager??new AdminLayoutManager();}
    public function defaults(): array{return $this->sanitize(data_get($this->layoutManager->defaults(),'design',config('admin.admin.design',[])));}
    public function tokens(): array{return $this->sanitize(data_get($this->layoutManager->config(),'design',config('admin.admin.design',[])));}

    public function sanitize(array $tokens): array
    {
        $defaults=config('admin.admin.design',[]);
        return [
            'typography'=>['font_family'=>$this->allowed(data_get($tokens,'typography.font_family'),array_keys(self::FONT_FAMILIES),data_get($defaults,'typography.font_family','sans')),'body_size'=>$this->allowed(data_get($tokens,'typography.body_size'),array_keys(self::FONT_SIZES),data_get($defaults,'typography.body_size','sm')),'page_title_size'=>$this->allowed(data_get($tokens,'typography.page_title_size'),array_keys(self::FONT_SIZES),data_get($defaults,'typography.page_title_size','2xl')),'heading_weight'=>$this->allowed(data_get($tokens,'typography.heading_weight'),array_keys(self::FONT_WEIGHTS),data_get($defaults,'typography.heading_weight','semibold'))],
            'colors'=>$this->sanitizeColors($tokens,$defaults),'sidebar_menu'=>$this->sanitizeSidebarMenu($tokens,$defaults),
            'spacing'=>['tight'=>$this->allowed(data_get($tokens,'spacing.tight'),array_keys(self::SPACING_VALUES),data_get($defaults,'spacing.tight','2')),'control'=>$this->allowed(data_get($tokens,'spacing.control'),array_keys(self::SPACING_VALUES),data_get($defaults,'spacing.control','3')),'content'=>$this->allowed(data_get($tokens,'spacing.content'),array_keys(self::SPACING_VALUES),data_get($defaults,'spacing.content','4')),'section'=>$this->allowed(data_get($tokens,'spacing.section'),array_keys(self::SPACING_VALUES),data_get($defaults,'spacing.section','6'))],
            'radius'=>['control'=>$this->allowed(data_get($tokens,'radius.control'),array_keys(self::RADIUS_VALUES),data_get($defaults,'radius.control','lg')),'panel'=>$this->allowed(data_get($tokens,'radius.panel'),array_keys(self::RADIUS_VALUES),data_get($defaults,'radius.panel','lg')),'overlay'=>$this->allowed(data_get($tokens,'radius.overlay'),array_keys(self::RADIUS_VALUES),data_get($defaults,'radius.overlay','xl'))],
        ];
    }

    public function cssVariables(?array $tokens=null): array
    {
        $tokens=$this->sanitize($tokens??$this->tokens());$menu=$tokens['sidebar_menu'];
        return [
            '--admin-font-family'=>self::FONT_FAMILIES[$tokens['typography']['font_family']],'--admin-font-size-body'=>self::FONT_SIZES[$tokens['typography']['body_size']],'--admin-font-size-page-title'=>self::FONT_SIZES[$tokens['typography']['page_title_size']],'--admin-font-weight-heading'=>self::FONT_WEIGHTS[$tokens['typography']['heading_weight']],
            '--admin-surface-base'=>self::COLOR_VALUES[$tokens['colors']['surface_base']],'--admin-surface-raised'=>self::COLOR_VALUES[$tokens['colors']['surface_raised']],'--admin-text-primary'=>self::COLOR_VALUES[$tokens['colors']['text_primary']],'--admin-text-secondary'=>self::COLOR_VALUES[$tokens['colors']['text_secondary']],'--admin-text-muted'=>self::COLOR_VALUES[$tokens['colors']['text_muted']],'--admin-border-subtle'=>self::COLOR_VALUES[$tokens['colors']['border_subtle']],'--admin-accent'=>self::COLOR_VALUES[$tokens['colors']['accent']],'--admin-focus-ring'=>self::COLOR_VALUES[$tokens['colors']['focus_ring']],'--admin-success'=>self::COLOR_VALUES[$tokens['colors']['success']],'--admin-warning'=>self::COLOR_VALUES[$tokens['colors']['warning']],'--admin-danger'=>self::COLOR_VALUES[$tokens['colors']['danger']],'--admin-info'=>self::COLOR_VALUES[$tokens['colors']['info']],
            '--admin-header-theme-background'=>self::COLOR_VALUES[$tokens['colors']['header_background']],'--admin-footer-theme-background'=>self::COLOR_VALUES[$tokens['colors']['footer_background']],'--admin-page-theme-background'=>self::COLOR_VALUES[$tokens['colors']['page_background']],'--admin-content-theme-background'=>self::COLOR_VALUES[$tokens['colors']['content_background']],'--admin-sidebar-header-theme-background'=>self::COLOR_VALUES[$tokens['colors']['sidebar_header_background']],'--admin-sidebar-navigation-theme-background'=>self::COLOR_VALUES[$tokens['colors']['sidebar_navigation_background']],'--admin-sidebar-footer-theme-background'=>self::COLOR_VALUES[$tokens['colors']['sidebar_footer_background']],
            '--admin-sidebar-menu-font-family'=>self::MENU_FONT_FAMILIES[$menu['item']['font_family']],'--admin-sidebar-menu-font-size'=>self::MENU_FONT_SIZES[$menu['item']['font_size']],'--admin-sidebar-menu-font-weight'=>self::FONT_WEIGHTS[$menu['item']['font_weight']],'--admin-sidebar-menu-title-color'=>self::COLOR_VALUES[$menu['item']['title_color']],'--admin-sidebar-menu-icon-color'=>self::COLOR_VALUES[$menu['item']['icon_color']],'--admin-sidebar-menu-icon-size'=>$menu['item']['icon_size'].'px','--admin-sidebar-menu-item-height'=>$menu['item']['item_height'].'px','--admin-sidebar-menu-padding-x'=>$menu['item']['padding_x'].'px','--admin-sidebar-menu-padding-y'=>$menu['item']['padding_y'].'px','--admin-sidebar-menu-content-gap'=>$menu['item']['content_gap'].'px','--admin-sidebar-menu-item-gap'=>$menu['item']['item_gap'].'px',
            '--admin-sidebar-submenu-font-family'=>self::MENU_FONT_FAMILIES[$menu['submenu']['font_family']],'--admin-sidebar-submenu-font-size'=>self::MENU_FONT_SIZES[$menu['submenu']['font_size']],'--admin-sidebar-submenu-font-weight'=>self::FONT_WEIGHTS[$menu['submenu']['font_weight']],'--admin-sidebar-submenu-title-color'=>self::COLOR_VALUES[$menu['submenu']['title_color']],'--admin-sidebar-submenu-icon-color'=>self::COLOR_VALUES[$menu['submenu']['icon_color']],'--admin-sidebar-submenu-indent'=>$menu['submenu']['indent'].'px','--admin-sidebar-submenu-item-height'=>$menu['submenu']['item_height'].'px','--admin-sidebar-submenu-padding-x'=>$menu['submenu']['padding_x'].'px','--admin-sidebar-submenu-padding-y'=>$menu['submenu']['padding_y'].'px','--admin-sidebar-submenu-offset'=>$menu['submenu']['offset'].'px','--admin-sidebar-submenu-item-gap'=>$menu['submenu']['item_gap'].'px','--admin-sidebar-menu-group-gap'=>$menu['group']['gap'].'px',
            '--admin-sidebar-active-title-color'=>self::COLOR_VALUES[$menu['active']['title_color']],'--admin-sidebar-active-icon-color'=>self::COLOR_VALUES[$menu['active']['icon_color']],'--admin-sidebar-active-font-weight'=>self::FONT_WEIGHTS[$menu['active']['font_weight']],
            '--admin-sidebar-menu-active-border-color'=>self::COLOR_VALUES[$menu['active']['menu_border_color']],'--admin-sidebar-menu-active-border-width'=>self::BORDER_WIDTHS[$menu['active']['menu_border_width']],'--admin-sidebar-menu-active-border-style'=>$menu['active']['menu_border_style'],
            '--admin-sidebar-submenu-active-border-color'=>self::COLOR_VALUES[$menu['active']['submenu_border_color']],'--admin-sidebar-submenu-active-border-width'=>self::BORDER_WIDTHS[$menu['active']['submenu_border_width']],'--admin-sidebar-submenu-active-border-style'=>$menu['active']['submenu_border_style'],
            '--admin-space-tight'=>self::SPACING_VALUES[$tokens['spacing']['tight']],'--admin-space-control'=>self::SPACING_VALUES[$tokens['spacing']['control']],'--admin-space-content'=>self::SPACING_VALUES[$tokens['spacing']['content']],'--admin-space-section'=>self::SPACING_VALUES[$tokens['spacing']['section']],'--admin-radius-control'=>self::RADIUS_VALUES[$tokens['radius']['control']],'--admin-radius-panel'=>self::RADIUS_VALUES[$tokens['radius']['panel']],'--admin-radius-overlay'=>self::RADIUS_VALUES[$tokens['radius']['overlay']],
        ];
    }

    public function colorOptions(): array{return self::COLOR_VALUES;}
    public function surfaceColorOptions(): array{return array_intersect_key(self::COLOR_VALUES,array_flip(self::SURFACE_COLOR_KEYS));}
    public function fontFamilyOptions(): array{return self::FONT_LABELS;}
    public function menuFontFamilyOptions(): array{return ['inherit'=>'Kế thừa font Admin']+self::FONT_LABELS;}
    public function menuFontSizeOptions(): array{return ['12'=>'12 px','13'=>'13 px','sm'=>'14 px','15'=>'15 px','base'=>'16 px'];}
    public static function fontFamilyKeys(): array{return array_keys(self::FONT_FAMILIES);}
    public static function menuFontFamilyKeys(): array{return array_keys(self::MENU_FONT_FAMILIES);}
    public static function colorKeys(): array{return array_keys(self::COLOR_VALUES);}
    public static function surfaceColorKeys(): array{return self::SURFACE_COLOR_KEYS;}
    public function colorValue(mixed $token,string $fallback='#ffffff'): string{return self::COLOR_VALUES[(string)$token]??$fallback;}
    public function contrastVariables(mixed $token): array{$dark=$this->isDark($this->colorValue($token));return $dark?['--admin-text-primary'=>'#ffffff','--admin-text-secondary'=>'#e2e8f0','--admin-text-muted'=>'#cbd5e1','--admin-border-subtle'=>'rgb(255 255 255 / 0.18)']:['--admin-text-primary'=>'#0f172a','--admin-text-secondary'=>'#334155','--admin-text-muted'=>'#64748b','--admin-border-subtle'=>'rgb(15 23 42 / 0.12)'];}
    private function isDark(string $hex): bool{$hex=ltrim($hex,'#');if(strlen($hex)!==6)return false;$r=hexdec(substr($hex,0,2));$g=hexdec(substr($hex,2,2));$b=hexdec(substr($hex,4,2));return((0.2126*$r+0.7152*$g+0.0722*$b)/255)<0.56;}

    private function sanitizeSidebarMenu(array $tokens,array $defaults): array
    {
        $d=(array)data_get($defaults,'sidebar_menu',[]);$m=(array)data_get($tokens,'sidebar_menu',[]);$colors=array_keys(self::COLOR_VALUES);
        return [
            'item'=>['font_family'=>$this->allowed(data_get($m,'item.font_family'),array_keys(self::MENU_FONT_FAMILIES),data_get($d,'item.font_family','inherit')),'font_size'=>$this->allowed(data_get($m,'item.font_size'),array_keys(self::MENU_FONT_SIZES),data_get($d,'item.font_size','sm')),'font_weight'=>$this->allowed(data_get($m,'item.font_weight'),array_keys(self::FONT_WEIGHTS),data_get($d,'item.font_weight','medium')),'title_color'=>$this->allowed(data_get($m,'item.title_color'),$colors,data_get($d,'item.title_color','slate-900')),'icon_color'=>$this->allowed(data_get($m,'item.icon_color'),$colors,data_get($d,'item.icon_color','slate-400')),'icon_size'=>$this->allowed((string)data_get($m,'item.icon_size'),['16','18','20','22','24'],(string)data_get($d,'item.icon_size','20')),'item_height'=>$this->allowed((string)data_get($m,'item.item_height'),['40','44','48','52'],(string)data_get($d,'item.item_height','44')),'padding_x'=>$this->allowed((string)data_get($m,'item.padding_x'),['8','10','12','14','16'],(string)data_get($d,'item.padding_x','12')),'padding_y'=>$this->allowed((string)data_get($m,'item.padding_y'),['4','6','8','10','12'],(string)data_get($d,'item.padding_y','8')),'content_gap'=>$this->allowed((string)data_get($m,'item.content_gap'),['8','10','12','14','16'],(string)data_get($d,'item.content_gap','12')),'item_gap'=>$this->allowed((string)data_get($m,'item.item_gap'),['0','2','4','6','8'],(string)data_get($d,'item.item_gap','4'))],
            'submenu'=>['font_family'=>$this->allowed(data_get($m,'submenu.font_family'),array_keys(self::MENU_FONT_FAMILIES),data_get($d,'submenu.font_family','inherit')),'font_size'=>$this->allowed(data_get($m,'submenu.font_size'),array_keys(self::MENU_FONT_SIZES),data_get($d,'submenu.font_size','13')),'font_weight'=>$this->allowed(data_get($m,'submenu.font_weight'),array_keys(self::FONT_WEIGHTS),data_get($d,'submenu.font_weight','normal')),'title_color'=>$this->allowed(data_get($m,'submenu.title_color'),$colors,data_get($d,'submenu.title_color','slate-500')),'icon_color'=>$this->allowed(data_get($m,'submenu.icon_color'),$colors,data_get($d,'submenu.icon_color','slate-400')),'indent'=>$this->allowed((string)data_get($m,'submenu.indent'),['20','24','28','32','36'],(string)data_get($d,'submenu.indent','28')),'item_height'=>$this->allowed((string)data_get($m,'submenu.item_height'),['32','36','40','44'],(string)data_get($d,'submenu.item_height','36')),'padding_x'=>$this->allowed((string)data_get($m,'submenu.padding_x'),['8','10','12','14','16'],(string)data_get($d,'submenu.padding_x','12')),'padding_y'=>$this->allowed((string)data_get($m,'submenu.padding_y'),['2','4','6','8','10'],(string)data_get($d,'submenu.padding_y','6')),'offset'=>$this->allowed((string)data_get($m,'submenu.offset'),['8','10','12','14','16'],(string)data_get($d,'submenu.offset','12')),'item_gap'=>$this->allowed((string)data_get($m,'submenu.item_gap'),['0','2','4','6'],(string)data_get($d,'submenu.item_gap','2'))],
            'group'=>['gap'=>$this->allowed((string)data_get($m,'group.gap'),['2','4','6','8','12'],(string)data_get($d,'group.gap','4'))],
            'active'=>[
                'title_color'=>$this->allowed(data_get($m,'active.title_color'),$colors,data_get($d,'active.title_color','white')),'icon_color'=>$this->allowed(data_get($m,'active.icon_color'),$colors,data_get($d,'active.icon_color','white')),'font_weight'=>$this->allowed(data_get($m,'active.font_weight'),array_keys(self::FONT_WEIGHTS),data_get($d,'active.font_weight','semibold')),
                'menu_border_color'=>$this->allowed(data_get($m,'active.menu_border_color'),$colors,data_get($d,'active.menu_border_color','indigo-600')),'menu_border_width'=>$this->allowed((string)data_get($m,'active.menu_border_width'),array_keys(self::BORDER_WIDTHS),(string)data_get($d,'active.menu_border_width','0')),'menu_border_style'=>$this->allowed(data_get($m,'active.menu_border_style'),self::BORDER_STYLES,data_get($d,'active.menu_border_style','solid')),
                'submenu_border_color'=>$this->allowed(data_get($m,'active.submenu_border_color'),$colors,data_get($d,'active.submenu_border_color','indigo-600')),'submenu_border_width'=>$this->allowed((string)data_get($m,'active.submenu_border_width'),array_keys(self::BORDER_WIDTHS),(string)data_get($d,'active.submenu_border_width','0')),'submenu_border_style'=>$this->allowed(data_get($m,'active.submenu_border_style'),self::BORDER_STYLES,data_get($d,'active.submenu_border_style','solid')),
            ],
        ];
    }

    private function sanitizeColors(array $tokens,array $defaults): array{$keys=['surface_base','surface_raised','text_primary','text_secondary','text_muted','border_subtle','accent','focus_ring','success','warning','danger','info','header_background','footer_background','page_background','content_background','sidebar_header_background','sidebar_navigation_background','sidebar_footer_background'];$colors=[];foreach($keys as $key){$allowed=in_array($key,['page_background','content_background'],true)?self::SURFACE_COLOR_KEYS:array_keys(self::COLOR_VALUES);$colors[$key]=$this->allowed(data_get($tokens,'colors.'.$key),$allowed,data_get($defaults,'colors.'.$key,$this->fallbackColor($key)));}return $colors;}
    private function fallbackColor(string $key): string{return match($key){'surface_base','page_background'=>'slate-50','surface_raised','header_background','footer_background','content_background','sidebar_header_background','sidebar_navigation_background','sidebar_footer_background'=>'white','text_primary'=>'slate-900','text_secondary'=>'slate-700','text_muted'=>'slate-500','border_subtle'=>'slate-200','accent'=>'indigo-600','focus_ring'=>'indigo-500','success'=>'emerald-600','warning'=>'amber-500','danger'=>'rose-600','info'=>'sky-600',default=>'slate-900'};}
    private function allowed(mixed $value,array $allowed,mixed $fallback): mixed
    {
        if (in_array($value,$allowed,true)) return $value;
        if (is_string($value) && ctype_digit($value) && in_array((int)$value,$allowed,true)) return $value;
        return $fallback;
    }
}
