<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminShellPresentationService
{
    private const SPACING_REM = ['0'=>'0rem','1'=>'0.25rem','2'=>'0.5rem','3'=>'0.75rem','4'=>'1rem','5'=>'1.25rem','6'=>'1.5rem','8'=>'2rem','10'=>'2.5rem','12'=>'3rem'];
    public function __construct(private readonly AdminLayoutManager $layoutManager) {}
    public function context(): array
    {
        $config=$this->layoutManager->config(); $container=(string)data_get($config,'layout.container','screen-2xl'); $density=(string)data_get($config,'layout.density','comfortable');
        return ['container'=>$container,'density'=>$density,'container_class'=>$this->containerClass($container),'content_class'=>$this->containerClass($container),'content_padding_class'=>'','content_style'=>$this->contentStyle($config),'shell_style'=>$this->shellStyle($config),'reduced_motion'=>(bool)data_get($config,'layout.behavior.reduced_motion',true),'sidebar_expanded_width'=>(string)data_get($config,'sidebar.expanded_width','16rem'),'sidebar_collapsed_width'=>(string)data_get($config,'sidebar.collapsed_width','5rem'),'header_height'=>(string)data_get($config,'header.height','4rem'),'header_style'=>$this->headerStyle($config),'header_padding_x'=>$this->space(data_get($config,'header.presentation.padding_x','6')),'header_action_gap'=>$this->space(data_get($config,'header.presentation.action_gap','2')),'header_mode'=>(string)data_get($config,'header.presentation.mode','balanced'),'header_backdrop_blur'=>(bool)data_get($config,'header.presentation.backdrop_blur',true)];
    }
    private function containerClass(string $container): string { return match($container){'full'=>'w-full max-w-none','narrow'=>'w-full max-w-[60rem] mx-auto','7xl'=>'w-full max-w-7xl mx-auto',default=>'w-full max-w-screen-2xl mx-auto'}; }
    private function contentStyle(array $config): string
    {
        $desktopX=$this->space(data_get($config,'layout.spacing.content_padding_x','6')); $top=$this->space(data_get($config,'layout.spacing.content_padding_top','6')); $bottom=$this->space(data_get($config,'layout.spacing.content_padding_bottom','8')); $tabletX=$this->space(data_get($config,'layout.spacing.tablet_padding_x','5')); $mobileX=$this->space(data_get($config,'layout.spacing.mobile_padding_x','4')); $sectionGap=$this->space(data_get($config,'layout.spacing.section_gap','6')); $surface=$this->surface(data_get($config,'layout.surface.content_surface','transparent'),'--admin-content-theme-background','var(--admin-surface-raised)'); $border=data_get($config,'layout.surface.border','system')==='none'?'transparent':'var(--admin-border-subtle)'; $radius=$this->radius(data_get($config,'layout.surface.radius','lg'));
        return implode('; ',["--admin-content-padding-x: {$desktopX}","--admin-content-padding-x-tablet: {$tabletX}","--admin-content-padding-x-mobile: {$mobileX}","--admin-content-padding-top: {$top}","--admin-content-padding-bottom: {$bottom}","--admin-section-gap: {$sectionGap}","--admin-content-surface: {$surface}","--admin-layout-border: {$border}","--admin-layout-radius: {$radius}"]);
    }
    private function shellStyle(array $config): string { $background=$this->surface(data_get($config,'layout.surface.page_background','system'),'--admin-page-theme-background','var(--admin-surface-base)'); return "--admin-page-background: {$background}"; }
    private function headerStyle(array $config): string
    {
        $mode=(string)data_get($config,'header.presentation.background','system');
        $background=match($mode){'white'=>'#ffffff','transparent'=>'transparent',default=>'var(--admin-header-theme-background)'};
        $divider=data_get($config,'header.presentation.divider','subtle')==='none'?'transparent':'color-mix(in srgb, var(--admin-border-subtle) 72%, transparent)';
        $shadow=data_get($config,'header.presentation.shadow','subtle')==='none'?'none':'0 1px 0 var(--admin-header-divider), 0 4px 14px rgb(15 23 42 / 0.035)';
        $opacity=data_get($config,'header.presentation.backdrop_blur',true)&&$background!=='transparent'?'94%':'100%';
        $style=['--admin-header-theme-fallback: var(--admin-surface-raised)','--admin-header-background: '.$background,'--admin-header-background-opacity: '.$opacity,'--admin-header-divider: '.$divider,'--admin-header-shadow: '.$shadow];
        if($mode!=='transparent'){ $token=$mode==='white'?'white':data_get($config,'design.colors.header_background','white'); foreach(app(AdminDesignService::class)->contrastVariables($token) as $variable=>$value) $style[]=$variable.': '.$value; }
        return implode('; ',$style);
    }
    private function surface(mixed $value,string $systemVariable,string $fallback): string { return match((string)$value){'white'=>'#ffffff','slate-50'=>'#f8fafc','transparent'=>'transparent',default=>"var({$systemVariable}, {$fallback})"}; }
    private function radius(mixed $value): string { return match((string)$value){'none'=>'0rem','sm'=>'0.25rem','md'=>'0.375rem',default=>'0.5rem'}; }
    private function space(mixed $value): string { return self::SPACING_REM[(string)$value]??self::SPACING_REM['6']; }
}
